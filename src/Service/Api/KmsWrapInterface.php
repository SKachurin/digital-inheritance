<?php

declare(strict_types=1);

namespace App\Service\Api;

interface KmsWrapInterface
{
    /**
     * Wrap "inner" (base64) via KMS replica and return w_b64 (base64 of iv||ct||tag) or null.
     */
    public function wrapInner(
        int $userId,
        int $kmsNumber,
        string $inner_b64,
        string $h_b64,
        string $answerFp
    ): ?string;
}