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
    /**
     * @param array      $encryptedSet  [json1, json2, json3]
     * @param int        $customerId
     * @param string     $answer
     *
     * @return false|string
     */
    public function decryptEnvelopeReplicas(
        array $encryptedSet,
        int $customerId,
        string $answer
    ): false|string {

        $tokens = $this->getNoteTokens($customerId); // 3 KMS tokens

        foreach ($encryptedSet as $i => $jsonBlob) {

            if (!$jsonBlob) continue;

            $obj = json_decode($jsonBlob, true);
            if (!isset($obj['c'], $obj['s'], $obj['iv'])) continue;

            $salt = base64_decode($obj['s']);
            $iv   = base64_decode($obj['iv']);
            $ct   = base64_decode($obj['c']);

            $salt16 = substr($salt, 0, 16);

            // 1) derive userKey (Argon2)
            $userKey = $this->deriveUserKey($answer, $salt16);

            // 2) get correct replica token
            if (!isset($tokens[$i])) continue;
            $token = base64_decode($tokens[$i]);

            // 3) HKDF final key
            $finalKey = $this->hkdfSha256($userKey, $token, "\x01", 32);

            // 4) AES-GCM decrypt
            $plain = $this->decryptAesGcm($finalKey, $iv, $ct);
            if ($plain !== false) {
                return $plain;
            }
        }

        return false;
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
        $this->kmsKeys = array_values(array_filter([
            (string)$params->get('TEST_KMS_KEY_1'),
            (string)$params->get('TEST_KMS_KEY_2'),
            (string)$params->get('TEST_KMS_KEY_3'),
        ]));
    }

    public function getNoteTokens(int $customerId): array
    {
        return $this->generateDeterministicTokens($customerId . '|envelope-v1');
    }

    private function generateDeterministicTokens(string $context): array
    {
        $out = [];
        foreach ($this->kmsKeys as $kmsKey) {
            $mac = hash_hmac('sha256', $context, $kmsKey, true);
            $out[] = base64_encode($mac);
        }
        return $out;
    }
}
