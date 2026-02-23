<?php

declare(strict_types=1);

namespace App\Queue\Doctrine\Api;

use App\Repository\KmsRepository;
use App\Service\Api\KmsHealthCheckService;
use Doctrine\ORM\EntityManagerInterface;

final class KmsHealthCheckConsumer
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly KmsRepository $kmsRepository,
        private readonly KmsHealthCheckService $healthCheckService,
    ) {}

    public function __invoke(): void
    {
        $kmsRows = $this->kmsRepository->findAll();
        if ($kmsRows === []) {
            return;
        }

        $now = new \DateTimeImmutable();

        // Cache gatewayId => statuses (so we don't spam the same gateway N times)
        /** @var array<string, array<string, string>|null> $gatewayCache */
        $gatewayCache = [];

        foreach ($kmsRows as $kms) {
            $alias = $kms->getAlias();              // "kms1", "kms2", ...
            $gatewayIds = $kms->getGatewayIds();    // ["API_HEALTHCHECK_IP_1", "API_HEALTHCHECK_IP_2", ...]

            $matched = false;

            foreach ($gatewayIds as $gatewayId) {
                if (!is_string($gatewayId) || $gatewayId === '') {
                    continue;
                }

                if (!array_key_exists($gatewayId, $gatewayCache)) {
                    // 2 attempts total (same as your current code)
                    $gatewayCache[$gatewayId] = $this->fetchWithRetry($gatewayId, 2);
                }

                $statuses = $gatewayCache[$gatewayId];

                // gateway failed or invalid JSON
                if ($statuses === null) {
                    continue;
                }

                // gateway returned statuses, and includes our alias
                if (array_key_exists($alias, $statuses)) {
                    $kms->setLastHealth($statuses[$alias] === 'up');
                    $kms->setCheckDate($now);
                    $matched = true;
                    break;
                }
            }

            // If we couldn't match this KMS to any gateway response:
            // fail closed (unhealthy) and stamp check_date so UI doesn't show stale data.
            if (!$matched) {
                $kms->setLastHealth(false);
                $kms->setCheckDate($now);
            }
        }

        $this->em->flush();
    }

    /**
     * @return array<string, string>|null Map kms alias => "up|down"
     */
    private function fetchWithRetry(string $gatewayId, int $attempts): ?array
    {
        for ($i = 0; $i < $attempts; $i++) {
            $statuses = $this->healthCheckService->fetchStatusesForGatewayId($gatewayId);
            if (is_array($statuses)) {
                return $statuses;
            }
        }
        return null;
    }
}