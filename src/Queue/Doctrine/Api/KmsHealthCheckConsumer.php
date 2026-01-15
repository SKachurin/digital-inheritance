<?php

declare(strict_types=1);

namespace App\Queue\Doctrine\Api;

use App\Message\KmsHealthCheckMessage;
use App\Repository\KmsRepository;
use App\Service\Api\KmsHealthCheckService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

#[AsMessageHandler]
final class KmsHealthCheckConsumer
{
    public function __construct(
        private readonly KmsRepository $kmsRepository,
        private readonly KmsHealthCheckService $healthCheckService,
        private readonly EntityManagerInterface $em,
    ) {}

    public function __invoke(KmsHealthCheckMessage $message): void
    {
        $kmsRows = $this->kmsRepository->findAll();
        if ($kmsRows === []) {
            return;
        }

        // gatewayId => list<Kms>
        $byGateway = [];
        foreach ($kmsRows as $kms) {
            $gatewayIds = $kms->getGatewayIds();

            // Current model: 1 gateway per KMS row (stored as JSON array for future flexibility)
            $gatewayId = $gatewayIds[0] ?? null;
            if (!is_string($gatewayId) || $gatewayId === '') {
                continue;
            }

            $byGateway[$gatewayId][] = $kms;
        }

        if ($byGateway === []) {
            return;
        }

        $now = new \DateTimeImmutable();

        foreach ($byGateway as $gatewayId => $rowsForGateway) {
            $statuses = $this->fetchWithRetry($gatewayId, 2); // 2 attempts total

            // If gateway did not return valid statuses -> mark all as unhealthy (your rule)
            if ($statuses === null) {
                foreach ($rowsForGateway as $kms) {
                    $kms->setLastHealth(false);
                    $kms->setCheckDate($now);
                }
                continue;
            }

            // Success -> update each row by alias
            foreach ($rowsForGateway as $kms) {
                $alias = $kms->getAlias();
                if (!array_key_exists($alias, $statuses)) {
                    continue;
                }

                $kms->setLastHealth($statuses[$alias] === 'up');
                $kms->setCheckDate($now);
            }
        }

        $this->em->flush();
    }

    /**
     * @return array<string,string>|null Map alias => "up|down"
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