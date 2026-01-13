<?php

declare(strict_types=1);

namespace App\Service\Api;

interface KmsUnwrapInterface
{
    /**
     * Convenience: return first successful DEK (binary 32 bytes) or null.
     * @throws KmsRateLimitedExceptionService
     */
    public function unwrapDek(int $userId, string $h_b64, string $answerFp, array $replicas): ?string;

    /**
     * Return DEKs per kms_id (binary 32 bytes), e.g. ['kms1' => <32B>, 'kms3' => <32B>].
     * Missing key means that KMS failed or returned empty.
     *
     * @throws KmsRateLimitedExceptionService
     */
    public function unwrapDeks(int $userId, string $h_b64, string $answerFp, array $replicas): array;
}