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

        $baseKey = $params->get('encryption_key');
        if (!is_string($baseKey)) {
            throw new \InvalidArgumentException('baseKey string must be a string.');
        }

        $this->personalString = $personalString ?: (string)$params->get('personal_string');
        if (!is_string($this->personalString)) {
            throw new \InvalidArgumentException('Personal string must be string.');
        }

        $hashedPersonalString = hash('sha256', $this->personalString, true);
        $hashedBaseKey = hash('sha256', $baseKey, true);
        $finalKey = $hashedPersonalString ^ $hashedBaseKey; //xor

        if (strlen($finalKey) !== 32) {
            throw new Exception('Combined key is not 32 bytes long');
        }

        $this->key = $finalKey;
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

        // Generate a nonce (number used once)
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

        // Encrypt the data
        $encrypted = sodium_crypto_secretbox($data, $nonce, $this->key);

        // Combine nonce and encrypted data
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

        // Extract the nonce and encrypted message
        $nonce = substr($decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $ciphertext = substr($decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        return sodium_crypto_secretbox_open($ciphertext, $nonce, $this->key);
    }
}