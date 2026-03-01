<?php

declare(strict_types=1);

namespace App\Service\Api;

use Psr\Log\LoggerInterface;

final class KmsUnwrapSelector implements KmsUnwrapInterface
{
    public function __construct(
        private readonly string            $kmsMode,
        private readonly KmsGatewayService $gateway,
        private readonly KmsMockService    $mock,
        private readonly LoggerInterface   $logger,
    )
    {
    }

    public function unwrapInner(int $userId, string $h_b64, string $answerFp, array $replicas): ?string
    {
        $inners = $this->unwrapInners($userId, $h_b64, $answerFp, $replicas);

        foreach (['kms1', 'kms2', 'kms3'] as $kmsId) {
            if (isset($inners[$kmsId])) {
                return $inners[$kmsId];
            }
        }

        foreach ($inners as $inner) {
            return $inner;
        }

        return null;
    }

    public function unwrapInners(int $userId, string $h_b64, string $answerFp, array $replicas): array
    {
        $replicas = $this->normalizeReplicas($replicas);

        $mode = strtolower(trim($this->kmsMode));

        if ($mode === 'gateway') {
            $out = $this->gateway->unwrapInners($userId, $h_b64, $answerFp, $replicas);
            return $out;
        }

        $out = $this->mock->unwrapInners($userId, $h_b64, $answerFp, $replicas);
        return $out;
    }

    /**
     * Accept stored/UI shapes and normalize to gateway shape:
     * - supports 'c' (your DB/UI JSON)
     * - supports 'w' (older internal)
     * - produces 'w_b64' for gateway + mock
     */
    private function normalizeReplicas(array $replicas): array
    {
        $out = [];

        foreach ($replicas as $r) {
            if (!is_array($r)) continue;

            $kmsId = $r['kms_id'] ?? null;
            if (!is_string($kmsId) || $kmsId === '') continue;

            $w = null;

            if (isset($r['w_b64']) && is_string($r['w_b64']) && $r['w_b64'] !== '') {
                $w = $r['w_b64'];
            } elseif (isset($r['c']) && is_string($r['c']) && $r['c'] !== '') {
                $w = $r['c'];     // <<< KEY FIX (DB/UI)
            } elseif (isset($r['w']) && is_string($r['w']) && $r['w'] !== '') {
                $w = $r['w'];     // <<< legacy internal
            }

            if (!is_string($w) || $w === '') continue;

            $out[] = [
                'kms_id' => $kmsId,
                'w_b64'  => $w,
            ];
        }

        return $out;
    }
}