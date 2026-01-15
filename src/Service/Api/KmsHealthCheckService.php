<?php

declare(strict_types=1);

namespace App\Service\Api;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class KmsHealthCheckService
{
    private string $certPath;
    private string $keyPath;
    private string $caPath;

    public function __construct(
        private readonly ParameterBagInterface $params,
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
    ) {
        $this->certPath = (string) $this->params->get('API_HEALTHCHECK_CERT');
        $this->keyPath  = (string) $this->params->get('API_HEALTHCHECK_KEY');
        $this->caPath   = (string) $this->params->get('API_HEALTHCHECK_CA');
    }

    /**
     * @return array<string,string>|null Map kms_id => up|down
     */
    public function fetchStatusesForGatewayId(string $gatewayId, int $timeoutSeconds = 5): ?array
    {
        $baseUrl = $this->resolveEnvUrl($gatewayId);
        if ($baseUrl === '') {
            return null;
        }

        try {
            $response = $this->httpClient->request('GET', $baseUrl . '/kms/health/check', [
                'cafile'      => $this->caPath,
                'local_cert'  => $this->certPath,
                'local_pk'    => $this->keyPath,
                'verify_peer' => true,
                'verify_host' => true,
                'timeout'     => $timeoutSeconds,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error(sprintf(
                'KMS healthcheck HTTP error via %s (%s): %s',
                $baseUrl,
                $gatewayId,
                $e->getMessage()
            ));
            return null;
        }

        if ($response->getStatusCode() !== 200) {
            return null;
        }

        $body = json_decode($response->getContent(false), true);
        if (!is_array($body)) {
            $this->logger->error(sprintf('KMS healthcheck invalid JSON from %s', $baseUrl));
            return null;
        }

        $statuses = $body['statuses'] ?? null;
        if (!is_array($statuses)) {
            $this->logger->error(sprintf('KMS healthcheck missing/invalid statuses from %s', $baseUrl));
            return null;
        }

        $out = [];
        foreach ($statuses as $kmsId => $status) {
            if (!is_string($kmsId) || $kmsId === '') {
                continue;
            }
            if (!is_string($status) || $status === '') {
                continue;
            }

            $s = strtolower(trim($status));
            if ($s !== 'up' && $s !== 'down') {
                // ignore unknown values so they don't poison DB logic
                continue;
            }

            $out[$kmsId] = $s;
        }

        return $out;
    }

    private function resolveEnvUrl(string $envKey): string
    {
        $url = '';
        try {
            $v = $this->params->get($envKey);
            if (is_string($v)) {
                $url = $v;
            }
        } catch (\Throwable) {}

        if ($url === '') {
            $url = (string) (getenv($envKey) ?: ($_ENV[$envKey] ?? ''));
        }

        $url = trim($url);
        if ($url === '') {
            return '';
        }

        if (str_starts_with($url, 'http://')) {
            $url = 'https://' . substr($url, 7);
        }

        return rtrim($url, '/');
    }
}