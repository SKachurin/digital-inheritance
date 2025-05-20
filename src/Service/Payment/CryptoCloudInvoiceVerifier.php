<?php

declare(strict_types=1);

namespace App\Service\Payment;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class CryptoCloudInvoiceVerifier
{
    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly string              $apiKey,
        private readonly LoggerInterface     $logger,
    )
    {
    }

    public function verify(string $invoiceId): ?array
    {
        try {
            $response = $this->client->request('POST', 'https://api.cryptocloud.plus/v2/invoice/merchant/info', [
                'headers' => [
                    'Authorization' => 'Token ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'Accept-Encoding' => 'identity',
                    'User-Agent' => 'TheDigitalHeirBot/1.0',
                ],
                'json' => [
                    'uuids' => [$invoiceId],
                ],
            ]);

            if ($response->getStatusCode() !== 200) {
                $this->logger->error('CryptoCloud API error', [
                    'invoice_id' => $invoiceId,
                    'status_code' => $response->getStatusCode(),
                    'body' => $response->getContent(false),
                ]);
                return null;
            }

            $result = $response->toArray(false);

            return $result['result'][0] ?? null;

        } catch (\Throwable $e) {
            $this->logger->error('CryptoCloud API exception', [
                'invoice_id' => $invoiceId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
