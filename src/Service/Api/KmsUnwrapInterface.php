<?php

declare(strict_types=1);

namespace App\Service\Api;

interface KmsUnwrapInterface
{
    /**
     * Convenience: return first successful INNER blob (raw bytes) or null.
     * This is NOT necessarily 32 bytes anymore.
     *
     * @throws KmsRateLimitedExceptionService
     */
    public function unwrapInner(int $userId, string $h_b64, string $answerFp, array $replicas): ?string;

    /**
     * Return INNER blobs per kms_id (raw bytes), e.g. ['kms1' => <blob>, 'kms3' => <blob>].
     * Missing key means that KMS failed or returned empty.
     *
     * @return array<string,string>
     * @throws KmsRateLimitedExceptionService
     */
    public function unwrapInners(int $userId, string $h_b64, string $answerFp, array $replicas): array;
}