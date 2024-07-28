<?php


namespace App\Service;

use Exception;
use Random\RandomException;
use SodiumException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class CryptoService
{
    private string $key;

    /**
     * @throws Exception
     */
    public function __construct(
        ParameterBagInterface $params,
        ?string $personalString = null
    )
    {
        $baseKey = $params->get('encryption_key');

        if ($personalString === null) {
            $personalString = $params->get('personal_string');
        }

        if (!is_string($personalString)) {
            throw new \InvalidArgumentException('Personal string must be a string.');
        }
        $hashedPersonalString = hash('sha256',  $personalString, true);
        error_log("Hashed Personal String: " . bin2hex($hashedPersonalString));

        if (!is_string($baseKey)) {
            throw new \InvalidArgumentException('Personal string must be a string.');
        }
        if (strlen($baseKey) < 16 || strlen($hashedPersonalString) < 16) {
            throw new Exception('Keys are not long enough to perform the substitution');
        }

        //create the final key
        $baseKeyFirstPart = substr($baseKey, 0, 16); // Take first 16 bytes of base key
        $hashedPersonalStringFirstPart = substr($hashedPersonalString, 0, 16); // Take first 16 bytes of hashed personal string

        $finalKey = $baseKeyFirstPart . $hashedPersonalStringFirstPart;

        if (strlen($finalKey) !== 32) {
            throw new Exception('Combined key is not 32 bytes long');
        }
        error_log("Final Key (Hex): " . bin2hex($finalKey));

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
    public function decryptData(?string $encryptedData): false | string
    {
        if ($encryptedData === null) {
            return '';
        }

        // Decode the base64 encoded data
        $decoded = base64_decode($encryptedData);

        // Extract the nonce and encrypted message
        $nonce = substr($decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $ciphertext = substr($decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

        // Decrypt the message
        return sodium_crypto_secretbox_open($ciphertext, $nonce, $this->key);
    }
}