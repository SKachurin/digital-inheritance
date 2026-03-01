<?php

declare(strict_types=1);

namespace App\Service\Api;

use Psr\Log\LoggerInterface;

final class KmsMockService implements KmsUnwrapInterface, KmsWrapInterface
{
    public function __construct(
        private readonly KmsCrypto $crypto,
        private readonly string $testKmsKey1B64 = '',
        private readonly string $testKmsKey2B64 = '',
        private readonly string $testKmsKey3B64 = '',
        private readonly LoggerInterface   $logger,
    ) {}

    // ---------------------------
    // Wrap (already in your repo)
    // ---------------------------
    public function wrapInner(
        int $userId,
        int $kmsNumber,
        string $inner_b64,
        string $h_b64,
        string $answerFp
    ): ?string {
        $kmsId = 'kms' . $kmsNumber;

        $kmsSecretRaw = $this->getRawKmsSecret($kmsId);
        if ($kmsSecretRaw === null) {
            return null; // simulate "KMS down"
        }

        $innerRaw = base64_decode($inner_b64, true);
        if ($innerRaw === false || $innerRaw === '') {
            return null;
        }

        $Hraw = base64_decode($h_b64, true);
        if ($Hraw === false || strlen($Hraw) !== 32) {
            return null;
        }

        $key32 = $this->crypto->deriveKekPrime($kmsSecretRaw, $Hraw);
        $aad   = $this->crypto->buildAad($userId, $answerFp);

        $blob = $this->crypto->gcmEncrypt($innerRaw, $key32, $aad);

        return base64_encode($blob);
    }

    // ---------------------------
    // Unwrap (REQUIRED for new flow)
    // ---------------------------

    /**
     * Convenience: return first successful INNER (raw bytes) or null.
     */
    public function unwrapInner(int $userId, string $h_b64, string $answerFp, array $replicas): ?string
    {
        $all = $this->unwrapInners($userId, $h_b64, $answerFp, $replicas);

        foreach (['kms1', 'kms2', 'kms3'] as $kmsId) {
            if (isset($all[$kmsId])) {
                return $all[$kmsId];
            }
        }

        foreach ($all as $innerRaw) {
            return $innerRaw;
        }

        return null;
    }

    /**
     * Return INNER per kms_id (raw bytes), e.g. ['kms1' => <bytes>, 'kms3' => <bytes>]
     *
     * @return array<string,string>
     */
    public function unwrapInners(int $userId, string $h_b64, string $answerFp, array $replicas): array
    {
        // Validate H once (unwrapOneReplica will decode again, but keep this for early loud failure)
        $H = base64_decode($h_b64, true);
        if ($H === false || strlen($H) !== 32) {
            throw new \RuntimeException('Mock unwrap: invalid h_b64 (must decode to 32 bytes).');
        }

        $out = [];

        foreach ($replicas as $r) {
            if (!is_array($r)) {
                continue;
            }

            $kmsId = $r['kms_id'] ?? null;
            if (!is_string($kmsId) || $kmsId === '') {
                continue;
            }

            // accept both payload shapes
            $w_b64 = null;
            if (isset($r['w_b64']) && is_string($r['w_b64']) && $r['w_b64'] !== '') {
                $w_b64 = $r['w_b64'];
            } elseif (isset($r['w']) && is_string($r['w']) && $r['w'] !== '') {
                $w_b64 = $r['w'];
            }

            if (!is_string($w_b64) || $w_b64 === '') {
                continue;
            }

            $inner = $this->unwrapOneReplica($userId, $kmsId, $h_b64, $answerFp, $w_b64);

            if ($inner === null) {
                continue;
            }

            $out[$kmsId] = $inner;
        }
        return $out;
    }


    // ---------------------------
    // Helpers
    // ---------------------------

    private function unwrapOneReplica(
        int $userId,
        string $kmsId,
        string $h_b64,
        string $answerFp,
        string $w_b64
    ): ?string {
        $kmsSecretRaw = $this->getRawKmsSecret($kmsId);
        if ($kmsSecretRaw === null) {
            $this->logger->warning('Mock unwrap: kms secret null', ['kms' => $kmsId]);
            return null;
        }

        $Hraw = base64_decode($h_b64, true);
        if ($Hraw === false || strlen($Hraw) !== 32) {
            return null;
        }

        $wRaw = base64_decode($w_b64, true);
        if ($wRaw === false || strlen($wRaw) < (12 + 16 + 1)) {
            return null;
        }

        $key32 = $this->crypto->deriveKekPrime($kmsSecretRaw, $Hraw);

        // Generate ALL possible fingerprint variants
        $fpVariants = [];

        // Original
        $fpVariants[] = $answerFp;
        $fpVariants[] = rtrim($answerFp, '=');

        // Try to detect if it's URL-safe or standard base64
        if (str_contains($answerFp, '-') || str_contains($answerFp, '_')) {
            // It's URL-safe, convert to standard
            $standard = strtr($answerFp, '-_', '+/');
            $fpVariants[] = $standard;
            $fpVariants[] = rtrim($standard, '=');
        } elseif (str_contains($answerFp, '+') || str_contains($answerFp, '/')) {
            // It's standard, convert to URL-safe
            $urlSafe = strtr($answerFp, '+/', '-_');
            $fpVariants[] = $urlSafe;
            $fpVariants[] = rtrim($urlSafe, '=');
        }

        $fpVariants = array_values(array_unique($fpVariants));

        // Generate AAD variants
        $aadVariants = [];
        foreach ($fpVariants as $fp) {
            $aadVariants[] = 'u=' . $userId . '|fp=' . $fp;
        }
        $aadVariants = array_unique($aadVariants);

        // Try all AAD candidates until one passes GCM auth
        $inner = false;
        $usedAad = null;

        foreach ($aadVariants as $aadTry) {
            $innerTry = $this->crypto->gcmDecrypt($wRaw, $key32, $aadTry);
            if ($innerTry !== false && $innerTry !== '') {
                $inner = $innerTry;
                $usedAad = $aadTry;
                break;
            }
        }

        if ($inner === false) {
            return null;
        }

        return $inner;
    }

    private function getTestKeyForAlias(string $kmsId): string
    {
        return match ($kmsId) {
            'kms1' => trim($this->testKmsKey1B64),
            'kms2' => trim($this->testKmsKey2B64),
            'kms3' => trim($this->testKmsKey3B64),
            default => '',
        };
    }

    private function getRawKmsSecret(string $kmsId): ?string
    {
        // 1) try injected constructor scalar first (works only if services.yaml binds it)
        $b64 = $this->getTestKeyForAlias($kmsId);

        // 2) fallback to env at runtime (your current case)
        if ($b64 === '') {
            $envName = match ($kmsId) {
                'kms1' => 'TEST_KMS_KEY_1',
                'kms2' => 'TEST_KMS_KEY_2',
                'kms3' => 'TEST_KMS_KEY_3',
                default => null,
            };

            if ($envName !== null) {
                $b64 = (string) (getenv($envName) ?: ($_ENV[$envName] ?? ''));
                $b64 = trim($b64);
            }
        }

        if ($b64 === '') {
            $this->logger->warning('Mock KMS secret missing', ['kms' => $kmsId]);
            return null;
        }

        $raw = base64_decode($b64, true);
        if ($raw === false || $raw === '') {
            $this->logger->warning('Mock KMS secret invalid base64', [
                'kms' => $kmsId,
                'b64_len' => strlen($b64),
            ]);
            return null;
        }

        return $raw;
    }


    private function getMockKmsSecretRaw(string $kmsId): string
    {
        $envName = match ($kmsId) {
            'kms1' => 'TEST_KMS_KEY_1',
            'kms2' => 'TEST_KMS_KEY_2',
            'kms3' => 'TEST_KMS_KEY_3',
            default => throw new \RuntimeException("Mock unwrap: unknown kms_id $kmsId"),
        };

        $b64 = (string) ($_ENV[$envName] ?? getenv($envName) ?: '');
        if ($b64 === '') {
            throw new \RuntimeException("Mock unwrap: missing $envName");
        }

        $raw = base64_decode($b64, true);
        if ($raw === false || strlen($raw) !== 32) {
            throw new \RuntimeException("Mock unwrap: $envName must be base64 of 32 bytes");
        }

        return $raw;
    }
}