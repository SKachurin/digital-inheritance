<?php

declare(strict_types=1);

namespace App\Queue\Doctrine\Api;

use App\Message\KmsHealthCheckMessage;
use App\Repository\KmsRepository;
use App\Service\Api\KmsHealthCheckService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class KmsHealthCheckConsumer
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly KmsRepository $kmsRepository,
        private readonly KmsHealthCheckService $healthCheckService,
    ) {}

    public function __invoke(KmsHealthCheckMessage $message): void
    {
        $kmsRows = $this->kmsRepository->findAll();
        if ($kmsRows === []) {
            return;
        }

        $now = new \DateTimeImmutable();

        /** @var array<string, array<string, string>|null> $gatewayCache */
        $gatewayCache = [];

        foreach ($kmsRows as $kms) {
            $alias = $kms->getAlias();
            $gatewayIds = $kms->getGatewayIds();

            $matched = false;

            foreach ($gatewayIds as $gatewayId) {
                if (!is_string($gatewayId) || $gatewayId === '') {
                    continue;
                }

                if (!array_key_exists($gatewayId, $gatewayCache)) {
                    $gatewayCache[$gatewayId] = $this->fetchWithRetry($gatewayId, 2);
                }

                $statuses = $gatewayCache[$gatewayId];
                if ($statuses === null) {
                    continue;
                }

                if (array_key_exists($alias, $statuses)) {
                    $kms->setLastHealth($statuses[$alias] === 'up');
                    $kms->setCheckDate($now);
                    $matched = true;
                    break;
                }
            }

            if (!$matched) {
                $kms->setLastHealth(false);
                $kms->setCheckDate($now);
            }
        }

        $this->em->flush();
    }

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