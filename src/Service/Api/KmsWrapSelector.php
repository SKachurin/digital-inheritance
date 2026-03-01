<?php

declare(strict_types=1);

namespace App\Service\Api;

final class KmsWrapSelector implements KmsWrapInterface
{
    public function __construct(
        private readonly string $kmsMode,
        private readonly KmsGatewayService $gateway,
        private readonly KmsMockService $mock,
    ) {}

    public function wrapInner(
        int $userId,
        int $kmsNumber,
        string $inner_b64,
        string $h_b64,
        string $answerFp
    ): ?string {
        $mode = strtolower(trim($this->kmsMode));

        if ($mode === 'gateway') {
            // kmsNumber -> kmsId
            $kmsId = 'kms' . $kmsNumber;

            $out = $this->gateway->wrapPayloadsViaGateway(
                userId: $userId,
                payload_b64: $inner_b64, // contract field is dek_b64, but we're sending INNER
                h_b64: $h_b64,
                answerFp: $answerFp,
                kmsIds: [$kmsId]
            );

            return $out[$kmsId] ?? null;
        }

        return $this->mock->wrapInner($userId, $kmsNumber, $inner_b64, $h_b64, $answerFp);
    }
}