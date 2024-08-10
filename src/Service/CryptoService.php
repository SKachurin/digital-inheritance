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
//    private LoggerInterface $logger;

    private ?string $personalString;

    /**
     * @throws Exception
     */
    public function __construct(
        ParameterBagInterface $params,
//        LoggerInterface $logger,
        ?string $personalString = ''

    )
    {
//        $this->logger = $logger;
        $this->personalString = $personalString;

        $baseKey = $params->get('encryption_key');
        if (!is_string($baseKey)) {
            throw new \InvalidArgumentException('Personal string must be a string.');
        }

        if ($this->personalString === '') {
            $this->personalString = $params->get('personal_string');
//            $this->logger->info('NO STRING.',  ['$personalString' => $this->personalString]);
        }

//        $this->logger->info('$baseKey.', ['$baseKey' => $baseKey]);
//        $this->logger->info('$personalString.', ['$personalString' => $this->personalString]);


        if (!is_string($personalString)) {
            throw new \InvalidArgumentException('Personal string must be a string.');
        }
        $hashedPersonalString = hash('sha256',  $this->personalString, true);
        $hashedBaseKey = hash('sha256',  $baseKey, true);


        $finalKey = $hashedPersonalString ^ $hashedBaseKey; //xor
//        $this->logger->info('$finalKey.', ['$finalKey' => $finalKey]);

        if (strlen($finalKey) !== 32) {
            throw new Exception('Combined key is not 32 bytes long');
        }
//        error_log("Final Key (Hex): " . bin2hex($finalKey));
//        error_log("Hashed Personal String: " . bin2hex($hashedPersonalString));
//        error_log("Hashed baseKey String: " . bin2hex($hashedBaseKey));

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

//        $this->logger->info(' decryptData Nonce length:', ['length' => strlen($nonce)]);
//        $this->logger->info('NdecryptData once:', ['nonce' => bin2hex($nonce)]);

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
//        $this->logger->info(' decryptData $nonce.', ['$nonce' => bin2hex($nonce)]);

        $ciphertext = substr($decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
//        $this->logger->info('decryptData $ciphertext.', ['$ciphertext' => $ciphertext]);

        // Decrypt the message
//        return
        return sodium_crypto_secretbox_open($ciphertext, $nonce, $this->key);
//        $this->logger->info('decryptData $x.', ['$x' => $x]);
//        return $x;
    }
}