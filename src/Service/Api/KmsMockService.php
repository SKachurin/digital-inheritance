<?php

declare(strict_types=1);

namespace App\Service\Api;

final class KmsMockService implements KmsUnwrapInterface
{
    public function __construct(
        private readonly string $testKmsKey1B64 = '',
        private readonly string $testKmsKey2B64 = '',
        private readonly string $testKmsKey3B64 = '',
    ) {}

    private function getTestKeyForAlias(string $kmsId): string
    {
        return match ($kmsId) {
            'kms1' => trim($this->testKmsKey1B64),
            'kms2' => trim($this->testKmsKey2B64),
            'kms3' => trim($this->testKmsKey3B64),
            default => '',
        };
    }

    private function hkdfSha256(string $ikm, string $salt, string $info, int $len): string
    {
        return hash_hkdf('sha256', $ikm, $len, $info, $salt);
    }

    /**
     * Convenience: return first successful DEK (binary 32 bytes) or null.
     */
    public function unwrapDek(int $userId, string $h_b64, string $answerFp, array $replicas): ?string
    {
        $all = $this->unwrapDeks($userId, $h_b64, $answerFp, $replicas);

        // deterministic order if you want:
        foreach (['kms1', 'kms2', 'kms3'] as $kmsId) {
            if (isset($all[$kmsId])) {
                return $all[$kmsId];
            }
        }

        // fallback: any
        foreach ($all as $dek) {
            return $dek;
        }

        return null;
    }

    /**
     * Return DEKs per kms_id (binary 32 bytes), e.g. ['kms1' => <32B>, 'kms3' => <32B>].
     */
    public function unwrapDeks(int $userId, string $h_b64, string $answerFp, array $replicas): array
    {
        $out = [];

        foreach ($replicas as $r) {
            if (!is_array($r)) {
                continue;
            }

            $kmsId = $r['kms_id'] ?? null;

            // DB payload uses "w"
            $wB64 = $r['w'] ?? ($r['w_b64'] ?? null);

            if (!is_string($kmsId) || $kmsId === '') {
                continue;
            }
            if (!is_string($wB64) || trim($wB64) === '') {
                continue;
            }

            $dek = $this->unwrapOneReplica($userId, $kmsId, $h_b64, $answerFp, $wB64);
            if ($dek !== null) {
                $out[$kmsId] = $dek; // raw 32 bytes
            }
        }

        return $out;
    }

    private function unwrapOneReplica(
        int $userId,
        string $kmsId,
        string $h_b64,
        string $answerFp,
        string $w_b64
    ): ?string {
        $testKeyB64 = $this->getTestKeyForAlias($kmsId);
        if ($testKeyB64 === '') {
            return null; // KMS down
        }

        // TEST_KMS_KEY_* is base64 in env/params → decode to raw bytes
        $testKey = base64_decode($testKeyB64, true);
        if ($testKey === false || $testKey === '') {
            return null;
        }

        $H = base64_decode($h_b64, true);
        if ($H === false || strlen($H) !== 32) {
            return null;
        }

        $wRaw = base64_decode($w_b64, true);
        if ($wRaw === false || strlen($wRaw) < (12 + 16 + 1)) {
            return null;
        }

        $iv   = substr($wRaw, 0, 12);
        $rest = substr($wRaw, 12);
        $tag  = substr($rest, -16);
        $ct   = substr($rest, 0, -16);

        $kek = hash('sha256', $testKey, true);                 // 32B
        $kekPrime = $this->hkdfSha256($kek, $H, 'wrap-v2', 32);

        $aad = $userId . '|' . $answerFp;

        $dek = openssl_decrypt(
            $ct,
            'aes-256-gcm',
            $kekPrime,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            $aad
        );

        if (!is_string($dek) || strlen($dek) !== 32) {
            return null;
        }

        return $dek;
    }
}
