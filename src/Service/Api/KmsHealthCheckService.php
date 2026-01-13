<?php

declare(strict_types=1);

namespace App\Service\Api;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class KmsHealthCheckService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,

        // inject these via parameters/env
        private readonly string $mtlsCertPath,
        private readonly string $mtlsKeyPath,
        private readonly string $mtlsCaPath,
        /** @var array<string,string> gatewayId => baseUrl */
        private readonly array $gatewayBaseUrls,
        private readonly float $timeoutSeconds = 5.0,
    ) {}

    public function fetchStatusesForGatewayId(string $gatewayId): ?array
    {
        $base = $this->gatewayBaseUrls[$gatewayId] ?? null;
        if (!$base) {
            return null;
        }

        try {
            $response = $this->httpClient->request('GET', rtrim($base, '/') . '/kms/health/check', [
                'timeout' => $this->timeoutSeconds,
                'local_cert' => $this->mtlsCertPath,
                'local_pk'   => $this->mtlsKeyPath,
                'cafile'     => $this->mtlsCaPath,
                'verify_peer' => true,
                'verify_host' => true,
            ]);

            $code = $response->getStatusCode();

            // Treat 429/5xx/etc as "no update" (or "fail all" if you prefer)
            if ($code === 429 || $code < 200 || $code >= 300) {
                return null;
            }

            $data = $response->toArray(false);
            $statuses = $data['statuses'] ?? null;

            return is_array($statuses) ? $statuses : null;
        } catch (\Throwable $e) {
            $this->logger->error('KMS healthcheck exception', [
                'gatewayId' => $gatewayId,
                'exception' => $e->getMessage(),
            ]);
            return null;
        }
    }

}