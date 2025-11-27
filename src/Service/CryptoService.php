<?php

namespace App\Service;

use Exception;
use Random\RandomException;
use SodiumException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Psr\Log\LoggerInterface;

class CryptoService
{
    private string $key;
    private LoggerInterface $logger;

    private ?string $personalString;

    /** @var array<string> */
    private array $kmsKeys = [];

    /**
     * @throws Exception
     */
    public function __construct(
        ParameterBagInterface $params,
        LoggerInterface $logger,
        ?string $personalString = ''
    )
    {
        $this->logger = $logger;
        $this->personalString = $personalString;

        // Load platform base key
        $baseKey = $params->get('encryption_key');
        if (!is_string($baseKey)) {
            throw new \InvalidArgumentException('baseKey must be string');
        }

        // Load personal string (answer)
        $this->personalString = $personalString ?: (string)$params->get('personal_string');
        if (!is_string($this->personalString)) {
            throw new \InvalidArgumentException('personal_string must be string');
        }

        // Legacy XOR key derivation
        $hashedPersonal = hash('sha256', $this->personalString, true);
        $hashedBase     = hash('sha256', $baseKey, true);
        $finalKey       = $hashedPersonal ^ $hashedBase;

        if (strlen($finalKey) !== 32) {
            throw new Exception("Combined key is not 32 bytes long");
        }

        $this->key = $finalKey;

        // Load mock KMS keys
        $this->loadKmsKeys($params);
    }

    /**
     * @throws RandomException
     * @throws SodiumException
     */
    public function encryptData(?string $data): ?string
    {
        if ($data === null) {
            return '';
        }

        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $encrypted = sodium_crypto_secretbox($data, $nonce, $this->key);

        return base64_encode($nonce . $encrypted);
    }

    /**
     * @throws SodiumException
     */
    public function decryptData(?string $encryptedData): false|string
    {
        if ($encryptedData === null || $encryptedData === '') {
            return '';
        }

        $decoded = base64_decode($encryptedData);

        $nonce = substr($decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $ciphertext = substr($decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

        return sodium_crypto_secretbox_open($ciphertext, $nonce, $this->key);
    }

    //  ENVELOPE MULTI-REPLICA DECRYPTION  (server-side)
    public function decryptEnvelopeReplicas(array $encryptedSet, int $customerId, string $answer): false|string
    {
        foreach ($encryptedSet as $i => $jsonBlob) {
            if (!$jsonBlob) continue;
            $obj = json_decode($jsonBlob, true);
            if (!isset($obj['c'], $obj['iv'], $obj['w'], $obj['s'])) continue;

            $c  = base64_decode($obj['c']);
            $iv = base64_decode($obj['iv']);
            $w  = base64_decode($obj['w']);
            $s  = base64_decode($obj['s']); // 16 bytes

            $c_b64 = $obj['c'];   // keep the base64 strings as-is
            $iv_b64 = $obj['iv'];
            $s_b64  = $obj['s'];  // base64 salt

            $answerFp = base64_encode(hash('sha256', $c_b64 . '.' . $iv_b64 . '.' . $s_b64, true));

            // derive H' from typed answer + stored salt
            $H = sodium_crypto_pwhash(
                32,
                $answer,
                $s,
                5,                 // opslimit
                64 * 1024 * 1024,  // memlimit
                SODIUM_CRYPTO_PWHASH_ALG_ARGON2ID13
            );


            $kmsId = ($i % 3) + 1; // slot 0→KMS1, 1→KMS2, 2→KMS3 (consistent with create)
            $dek = $this->kmsUnwrap($customerId, $kmsId, $w, $H, $answerFp);
            if ($dek === false) continue;

            // decrypt AES-GCM (ciphertext includes tag at the end)
            $TAG_LEN = 16;
            $tag = substr($c, -$TAG_LEN);
            $ct  = substr($c, 0, -$TAG_LEN);

            $plain = openssl_decrypt($ct, 'aes-256-gcm', $dek, OPENSSL_RAW_DATA, $iv, $tag, '');
            if ($plain !== false) return $plain;
        }
        return false;
    }

    //  ENVELOPE MULTI-REPLICA DECRYPTION  (per slot)
    public function decryptEnvelopeReplicasPerSlot(
        array $encryptedSet,
        int $customerId,
        string $answer
    ): array {
        $results = [];

        foreach ($encryptedSet as $i => $jsonBlob) {
            if (!$jsonBlob) {
                $results[$i] = null;
                continue;
            }

            $obj = json_decode($jsonBlob, true);
            if (!isset($obj['c'], $obj['iv'], $obj['w'], $obj['s'])) {
                $results[$i] = $jsonBlob;
                continue;
            }

            $c  = base64_decode($obj['c']);
            $iv = base64_decode($obj['iv']);
            $w  = base64_decode($obj['w']);
            $s  = base64_decode($obj['s']);

            $c_b64 = $obj['c'];
            $iv_b64 = $obj['iv'];
            $s_b64  = $obj['s'];

            $answerFp = base64_encode(
                hash('sha256', $c_b64 . '.' . $iv_b64 . '.' . $s_b64, true)
            );

            $H = sodium_crypto_pwhash(
                32,
                $answer,
                $s,
                5,
                64 * 1024 * 1024,
                SODIUM_CRYPTO_PWHASH_ALG_ARGON2ID13
            );

            $kmsId = ($i % 3) + 1;

            $dek = $this->kmsUnwrap($customerId, $kmsId, $w, $H, $answerFp);
            if ($dek === false) {
                // KMS missing / unwrap failed → keep encrypted JSON
                $results[$i] = $jsonBlob;
                continue;
            }

            $TAG_LEN = 16;
            $tag = substr($c, -$TAG_LEN);
            $ct  = substr($c, 0, -$TAG_LEN);

            $plain = openssl_decrypt(
                $ct,
                'aes-256-gcm',
                $dek,
                OPENSSL_RAW_DATA,
                $iv,
                $tag,
                ''
            );

            // if auth fails, still show ciphertext
            $results[$i] = ($plain === false) ? $jsonBlob : $plain;
        }

        return $results;
    }


    //  INTERNAL SUPPORT FUNCTIONS
    private function deriveUserKey(string $answer, string $salt16): string
    {
        // Align with browser: time=5, mem=64MiB, Argon2id, 32 bytes
        $opslimit = 5;
        $memlimit = 64 * 1024 * 1024; // bytes

        return sodium_crypto_pwhash(
            32,
            $answer,
            $salt16,
            $opslimit,
            $memlimit,
            SODIUM_CRYPTO_PWHASH_ALG_ARGON2ID13
        );
    }

    private function decryptAesGcm(string $key, string $iv, string $ct): false|string
    {
        try {
            $TAG_LEN = 16; // 128-bit tag (WebCrypto default)

            if (strlen($ct) < $TAG_LEN) {
                return false;
            }

            $tag        = substr($ct, -$TAG_LEN);
            $ciphertext = substr($ct, 0, -$TAG_LEN);

            return openssl_decrypt(
                $ciphertext,
                'aes-256-gcm',
                $key,
                OPENSSL_RAW_DATA,
                $iv,
                $tag,
                '' // no AAD
            );
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function hkdfSha256(string $ikm, string $salt, string $info, int $len): string
    {
        return hash_hkdf('sha256', $ikm, $len, $info, $salt);
    }


    //  KMS TOKEN MOCK SYSTEM
    private function loadKmsKeys(ParameterBagInterface $params): void
    {
        $this->kmsKeys = [
            1 => (string) $params->get('TEST_KMS_KEY_1'),
            2 => (string) $params->get('TEST_KMS_KEY_2'),
            3 => (string) $params->get('TEST_KMS_KEY_3'),
        ];
    }

    public function kmsWrap(
        int $customerId,
        int $kmsId,
        string $dek,
        string $H,
        string $answerFp
    ): string|false {
        $kek = $this->getKekFor($kmsId);

        // Disabled KMS → do NOT wrap at all
        if ($kek === '') {
            $this->logger->info(sprintf('KMS[%d] disabled, skipping wrap', $kmsId));
            return false;
        }

        $kekPrime = hash_hkdf('sha256', $kek, 32, 'wrap-v2', $H);

        $iv  = random_bytes(12);
        $aad = $customerId . '|' . $answerFp;

        $cipher = openssl_encrypt(
            $dek,
            'aes-256-gcm',
            $kekPrime,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            $aad
        );

        if ($cipher === false) {
            $this->logger->error(sprintf('KMS[%d] wrap failed', $kmsId));
            return false;
        }

        // Valid wrapped DEK (raw)
        return $iv . $cipher . $tag;
    }


    public function kmsUnwrap(int $customerId, int $kmsId, string $wRaw, string $H, string $answerFp): false|string
    {
        $kek = $this->getKekFor($kmsId);

        // disabled KMS
        if ($kek === '') {
//            $this->logger->info(sprintf('KMS[%d] disabled, skipping unwrap', $kmsId));
            return false;
        }

        $kekPrime = hash_hkdf('sha256', $kek, 32, 'wrap-v2', $H);

        $iv   = substr($wRaw, 0, 12);
        $rest = substr($wRaw, 12);
        $tag  = substr($rest, -16);
        $ct   = substr($rest, 0, -16);

        $aad = $customerId . '|' . $answerFp;

        try {
            $dek = openssl_decrypt(
                $ct,
                'aes-256-gcm',
                $kekPrime,
                OPENSSL_RAW_DATA,
                $iv,
                $tag,
                $aad
            );
            return $dek === false ? false : $dek;
        } catch (\Throwable) {
            return false;
        }
    }

    private function getKekFor(int $kmsId): string
    {
        if (!array_key_exists($kmsId, $this->kmsKeys)) {
            throw new \InvalidArgumentException('bad kms id');
        }

        $k = (string) $this->kmsKeys[$kmsId];

        // Disabled → empty string, kmsWrap decides what to do
        if ($k === '') {
            return '';
        }

        return hash('sha256', $k, true);
    }
}
