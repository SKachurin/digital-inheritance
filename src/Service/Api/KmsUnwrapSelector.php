<?php

declare(strict_types=1);

namespace App\Service\Api;

final class KmsUnwrapSelector implements KmsUnwrapInterface
{
    public function __construct(
        private readonly string $kmsMode,
        private readonly KmsGatewayService $gateway,
        private readonly KmsMockService $mock,
    ) {}

    public function unwrapDek(int $userId, string $h_b64, string $answerFp, array $replicas): ?string
    {
        $deks = $this->unwrapDeks($userId, $h_b64, $answerFp, $replicas);
        foreach ($deks as $dek) {
            if (is_string($dek) && strlen($dek) === 32) {
                return $dek;
            }
        }
        return null;
    }

    public function unwrapDeks(int $userId, string $h_b64, string $answerFp, array $replicas): array
    {
        $mode = strtolower(trim($this->kmsMode));

        if (in_array($mode, ['gateway', 'prod', 'real'], true)) {
            return $this->gateway->unwrapDeks($userId, $h_b64, $answerFp, $replicas);
        }

        return $this->mock->unwrapDeks($userId, $h_b64, $answerFp, $replicas);
    }
}