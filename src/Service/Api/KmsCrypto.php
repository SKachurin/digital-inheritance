<?php

declare(strict_types=1);

namespace App\Service\Api;

/**
 * One place that defines:
 * - KEK derivation
 * - HKDF parameters
 * - AAD format
 * Both WRAP and UNWRAP must use this.
 */
final class KmsCrypto
{
    public function buildAad(int $userId, string $answerFp): string
    {
        // DO NOT change this string format unless you re-wrap everything.
        return 'u=' . $userId . '|fp=' . $answerFp;
    }

    /**
     * @param string $kmsSecretRaw raw bytes of the KMS secret (decoded from base64 env)
     * @param string $Hraw        raw 32-byte H
     */
    public function deriveKekPrime(string $kmsSecretRaw, string $Hraw): string
    {
        // Keep identical to what you already do in mock unwrap:
        // kek = sha256(kmsSecretRaw)
        // kek' = HKDF-SHA256(ikm=kek, salt=H, info="wrap-v2", L=32)
        $kek = hash('sha256', $kmsSecretRaw, true);

        return hash_hkdf(
            'sha256',
            $kek,
            32,
            'wrap-v2',
            $Hraw
        );
    }

    /**
     * Wrap raw bytes using AES-256-GCM.
     * Output raw bytes: iv(12) || ct || tag(16)
     */
    public function gcmEncrypt(string $plaintext, string $key32, string $aad): string
    {
        $iv = random_bytes(12);
        $tag = '';

        $ct = openssl_encrypt(
            $plaintext,
            'aes-256-gcm',
            $key32,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            $aad,
            16
        );

        if (!is_string($ct) || $ct === '' || !is_string($tag) || strlen($tag) !== 16) {
            throw new \RuntimeException('gcmEncrypt failed');
        }

        return $iv . $ct . $tag;
    }

    /**
     * Decrypt raw bytes: iv(12) || ct || tag(16)
     */
    public function gcmDecrypt(string $blob, string $key32, string $aad): string|false
    {
        if (strlen($blob) < (12 + 16 + 1)) {
            return false;
        }

        $iv  = substr($blob, 0, 12);
        $rest = substr($blob, 12);
        $tag = substr($rest, -16);
        $ct  = substr($rest, 0, -16);

        return openssl_decrypt(
            $ct,
            'aes-256-gcm',
            $key32,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            $aad
        );
    }

    public function deriveInnerWrapKeyFromH(string $Hraw, int $userId, string $answerFp): string
    {
        // Must match FE:
        // infoStr = `inner-wrap-v1|u=${userId}|fp=${answerFp}`
        // HKDF salt = empty
        $info = sprintf('inner-wrap-v1|u=%d|fp=%s', $userId, $answerFp);

        // We need a 32-byte key for openssl AES-256-GCM.
        // Equivalent to WebCrypto HKDF->AES key derivation.
        return hash_hkdf('sha256', $Hraw, 32, $info, '');
    }

    public function buildInnerAad(int $userId, string $answerFp): string
    {
        // Must match FE additionalData: `u=${userId}|fp=${answerFp}`
        return sprintf('u=%d|fp=%s', $userId, $answerFp);
    }

    /**
     * Decrypt INNER blob (raw bytes: iv||ct||tag) into DEK (32 bytes).
     */
    public function innerUnwrapDek(string $innerBlobRaw, string $Hraw, int $userId, string $answerFp): string|false
    {
        $key32 = $this->deriveInnerWrapKeyFromH($Hraw, $userId, $answerFp);
        $aad   = $this->buildInnerAad($userId, $answerFp);

        $plain = $this->gcmDecrypt($innerBlobRaw, $key32, $aad);

        if (!is_string($plain) || strlen($plain) !== 32) {
            return false;
        }

        return $plain;
    }
}