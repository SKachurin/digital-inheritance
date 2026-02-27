<?php

declare(strict_types=1);

namespace App\Service;

use App\Service\Api\KmsRateLimitedExceptionService;
use App\Service\Api\KmsUnwrapInterface;
use Psr\Log\LoggerInterface;
use Random\RandomException;
use SodiumException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

final class CryptoService
{
    private string $key;
    private ?string $personalString = null;

    public function __construct(
        private readonly ParameterBagInterface $params,
        private readonly LoggerInterface $logger,
        private readonly KmsUnwrapInterface $kmsUnwrap,
        ?string $personalString = null
    ) {
        $baseKey = $this->params->get('encryption_key');
        if (!is_string($baseKey) || $baseKey === '') {
            throw new \InvalidArgumentException('encryption_key must be a non-empty string');
        }

        $this->personalString = $personalString ?: (string) $this->params->get('personal_string');
        if (!is_string($this->personalString) || $this->personalString === '') {
            throw new \InvalidArgumentException('personal_string must be a non-empty string');
        }

        $hashedPersonal = hash('sha256', $this->personalString, true);
        $hashedBase     = hash('sha256', $baseKey, true);
        $finalKey       = $hashedPersonal ^ $hashedBase;

        if (strlen($finalKey) !== 32) {
            throw new \RuntimeException('Combined key is not 32 bytes long');
        }

        $this->key = $finalKey;
    }

    /** @throws RandomException @throws SodiumException */
    public function encryptData(?string $data): ?string
    {
        if ($data === null) {
            return '';
        }

        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $encrypted = sodium_crypto_secretbox($data, $nonce, $this->key);

        return base64_encode($nonce . $encrypted);
    }

    /** @throws SodiumException */
    public function decryptData(?string $encryptedData): false|string
    {
        if ($encryptedData === null || $encryptedData === '') {
            return '';
        }

        $decoded = base64_decode($encryptedData, true);
        if ($decoded === false || strlen($decoded) < SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
            return false;
        }

        $nonce = substr($decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $ciphertext = substr($decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

        return sodium_crypto_secretbox_open($ciphertext, $nonce, $this->key);
    }

    /**
     * @param array{0 $tripletJson :?string,1:?string,2:?string} $tripletJson encrypted JSON blobs from DB
     * @return array{0:?string,1:?string,2:?string} plaintext per slot, null if not decrypted
     * @throws KmsRateLimitedExceptionService|SodiumException
     */
    public function decryptEnvelopeTripletForUi(array $tripletJson, int $customerId, string $answer): array
    {
        $result = [null, null, null];

        // collect valid JSON objects by slot index (0/1/2)
        $slots = [];
        foreach ([0, 1, 2] as $i) {
            $json = $tripletJson[$i] ?? null;
            if (!is_string($json) || trim($json) === '') {
                continue;
            }

            $obj = json_decode($json, true);
            if (!is_array($obj) || !isset($obj['c'], $obj['iv'], $obj['w'], $obj['s'])) {
                continue;
            }
            if (!is_string($obj['c']) || !is_string($obj['iv']) || !is_string($obj['w']) || !is_string($obj['s'])) {
                continue;
            }

            $slots[$i] = $obj;
        }

        if ($slots === []) {
            return $result;
        }

        // reference (c/iv/s must match across replicas)
        $first = reset($slots);
        $c_b64  = (string) $first['c'];
        $iv_b64 = (string) $first['iv'];
        $s_b64  = (string) $first['s'];

        $salt = base64_decode($s_b64, true);
        if ($salt === false || strlen($salt) !== 16) {
            return $result;
        }

        $answerFp = base64_encode(hash('sha256', $c_b64 . '.' . $iv_b64 . '.' . $s_b64, true));

        $H = sodium_crypto_pwhash(
            32,
            $answer,
            $salt,
            5,
            64 * 1024 * 1024,
            SODIUM_CRYPTO_PWHASH_ALG_ARGON2ID13
        );
        $h_b64 = base64_encode($H);

        $indexToKms = [0 => 'kms1', 1 => 'kms2', 2 => 'kms3'];

        $replicas = [];
        foreach ($slots as $i => $obj) {
            $kmsId = $indexToKms[$i] ?? null;
            if (!$kmsId) {
                continue;
            }
            $replicas[] = [
                'kms_id' => $kmsId,
                'w_b64'  => (string) $obj['w'],
            ];
        }

        if ($replicas === []) {
            return $result;
        }

        // IMPORTANT: no unknown mapping; we only trust per-kms deks map
        $deks = $this->kmsUnwrap->unwrapDeks($customerId, $h_b64, $answerFp, $replicas);

        foreach ($slots as $i => $obj) {
            $kmsId = $indexToKms[$i] ?? null;
            if (!$kmsId) {
                continue;
            }

            $innerBlob = $deks[$kmsId] ?? null;
            if (!is_string($innerBlob)) {
                continue;
            }

            // Handle if KMS returns base64 instead of raw bytes
            if (preg_match('/^[A-Za-z0-9+\/=]+$/', $innerBlob) && strlen($innerBlob) > 60) {
                $decoded = base64_decode($innerBlob, true);
                if ($decoded !== false) {
                    $innerBlob = $decoded;
                }
            }

            if (strlen($innerBlob) < (12 + 16 + 32)) { // 60 bytes minimum
                continue;
            }

            // Same info string format as JS
            $info = 'inner-wrap-v1|u=' . $customerId . '|fp=' . $answerFp;
            $wrapKey = hash_hkdf('sha256', $H, 32, $info, '');

            // Extract components
            $ivInner = substr($innerBlob, 0, 12);
            $tag = substr($innerBlob, -16);
            $ciphertext = substr($innerBlob, 12, -16);

            // Same AAD format as JS
            $aad = 'u=' . $customerId . '|fp=' . $answerFp;
            $dek = openssl_decrypt($ciphertext, 'aes-256-gcm', $wrapKey, OPENSSL_RAW_DATA, $ivInner, $tag, $aad);

            if ($dek === false || strlen($dek) !== 32) {
                continue;
            }

            // Now use the real DEK to decrypt the note
            $ct = base64_decode((string) $obj['c'], true);
            $ivNote = base64_decode((string) $obj['iv'], true);
            if ($ct === false || $ivNote === false) {
                continue;
            }

            $plain = $this->decryptAesGcm($dek, $ivNote, $ct);
            if ($plain !== false) {
                $result[$i] = $plain;
            }
        }

        return $result;
    }

    private function decryptAesGcm(string $key32, string $iv12, string $ctWithTag): false|string
    {
        $TAG_LEN = 16;
        if (strlen($ctWithTag) < $TAG_LEN) {
            return false;
        }

        $tag        = substr($ctWithTag, -$TAG_LEN);
        $ciphertext = substr($ctWithTag, 0, -$TAG_LEN);

        try {
            $plain = openssl_decrypt(
                $ciphertext,
                'aes-256-gcm',
                $key32,
                OPENSSL_RAW_DATA,
                $iv12,
                $tag,
                ''
            );

            return $plain === false ? false : $plain;
        } catch (\Throwable) {
            return false;
        }
    }
}