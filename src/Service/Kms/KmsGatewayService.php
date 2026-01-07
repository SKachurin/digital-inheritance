<?php

namespace App\Service\Kms;

use App\Repository\KmsRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class KmsGatewayService
{
    private string $certPath;
    private string $keyPath;
    private string $caPath;

    public function __construct(
        private readonly ParameterBagInterface $params,
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly KmsRepository $kmsRepository,
    ) {
        $this->certPath = (string) $this->params->get('API_HEALTHCHECK_CERT');
        $this->keyPath  = (string) $this->params->get('API_HEALTHCHECK_KEY');
        $this->caPath   = (string) $this->params->get('API_HEALTHCHECK_CA');
    }

    /**
     * @return array<string,string> map kms_id => binary 32B DEK
     * @throws KmsRateLimitedExceptionService
     */
    public function unwrapDeks(int $userId, string $h_b64, string $answerFp, array $replicas): array
    {
        $urls = $this->resolveGatewayUrlsForReplicas($replicas);
        if (!$urls) {
            throw new \RuntimeException('No KMS gateway URLs resolved from KMS.gateway_ids (and env).');
        }

        $payload = [
            'user_id'   => $userId,
            'h_b64'     => $h_b64,
            'answer_fp' => $answerFp,
            'replicas'  => $replicas,
        ];

        foreach ($urls as $baseUrl) {
            try {
                $response = $this->httpClient->request('POST', $baseUrl . '/kms/unwrap', [
                    'json' => $payload,

                    'cafile'     => $this->caPath,
                    'local_cert' => $this->certPath,
                    'local_pk'   => $this->keyPath,

                    'verify_peer' => true,
                    'verify_host' => true,
                    'timeout'     => 10,
                ]);
            } catch (\Throwable $e) {
                $this->logger->error(sprintf('KMS unwrap HTTP error via %s: %s', $baseUrl, $e->getMessage()));
                continue;
            }

            $status = $response->getStatusCode();

            if ($status === 429) {
                $raw = $response->getContent(false);
                $body = json_decode($raw, true) ?: [];

                $retryAfter = 0;
                if (isset($body['retry_after_seconds'])) {
                    $retryAfter = (int) $body['retry_after_seconds'];
                } else {
                    $headers = $response->getHeaders(false);
                    $ra = $headers['retry-after'][0] ?? null;
                    if ($ra !== null) {
                        $retryAfter = (int) $ra;
                    }
                }

                throw new KmsRateLimitedExceptionService(max(1, $retryAfter));
            }

            if ($status !== 200) {
                $this->logger->error(sprintf('KMS unwrap unexpected HTTP %d from %s', $status, $baseUrl));
                continue;
            }

            $body = json_decode($response->getContent(false), true);
            if (!is_array($body)) {
                $this->logger->error(sprintf('KMS unwrap invalid JSON from %s', $baseUrl));
                continue;
            }

            $deksOut = [];

            // contract: deks_b64 required; empty string means failure
            $deks_b64 = $body['deks_b64'] ?? null;
            if (!is_array($deks_b64)) {
                $this->logger->error(sprintf('KMS unwrap missing/invalid deks_b64 from %s', $baseUrl));
                continue;
            }

            foreach ($deks_b64 as $kmsId => $dekB64) {
                if (!is_string($kmsId) || $kmsId === '') {
                    continue;
                }
                if (!is_string($dekB64) || $dekB64 === '') {
                    continue;
                }

                $dek = base64_decode($dekB64, true);
                if ($dek === false || strlen($dek) !== 32) {
                    $this->logger->error(sprintf('KMS unwrap malformed DEK for %s via %s', $kmsId, $baseUrl));
                    continue;
                }

                $deksOut[$kmsId] = $dek;
            }

            return $deksOut;
        }

        return [];
    }

    /**
     * Backward convenience: first success DEK or null.
     * @throws KmsRateLimitedExceptionService
     */
    public function unwrapDek(int $userId, string $h_b64, string $answerFp, array $replicas): ?string
    {
        $deks = $this->unwrapDeks($userId, $h_b64, $answerFp, $replicas);
        foreach ($deks as $dek) {
            return $dek;
        }
        return null;
    }

    private function resolveGatewayUrlsForReplicas(array $replicas): array
    {
        $aliases = [];
        foreach ($replicas as $r) {
            $alias = $r['kms_id'] ?? null;
            if (is_string($alias) && $alias !== '') {
                $aliases[$alias] = true;
            }
        }
        $aliases = array_keys($aliases);
        if (!$aliases) {
            return [];
        }

        $kmsList = $this->kmsRepository->findByAliases($aliases);

        $gatewayEnvKeys = [];
        foreach ($kmsList as $kms) {
            foreach ((array) $kms->getGatewayIds() as $envKey) {
                if (is_string($envKey) && $envKey !== '') {
                    $gatewayEnvKeys[$envKey] = true;
                }
            }
        }

        $urls = [];
        foreach (array_keys($gatewayEnvKeys) as $envKey) {
            $url = $this->resolveEnvUrl($envKey);
            if ($url !== '') {
                $urls[] = $url;
            }
        }

        $urls = array_values(array_unique($urls));
        shuffle($urls);

        return $urls;
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

    /**
     * Wrap a DEK via gateway (mTLS).
     * Returns w_b64 or null if unavailable.
     */
    public function wrapDek(
        int $userId,
        int $kmsNumber,
        string $dek_b64,
        string $h_b64,
        string $answerFp,
        array $replicasMeta = []
    ): ?string {
        // Resolve gateways from DB the same way unwrap does.
        $replicas = [['kms_id' => 'kms' . $kmsNumber, 'w_b64' => 'x']]; // dummy
        $urls = $this->resolveGatewayUrlsForReplicas($replicas);
        if (!$urls) {
            return null;
        }

        $kmsId = 'kms' . $kmsNumber; // ADDED: "kms1" etc.

        // gateway expects kms_ids array
        $payload = [
            'user_id'   => $userId,
            'dek_b64'   => $dek_b64,
            'h_b64'     => $h_b64,
            'answer_fp' => $answerFp,
            'kms_ids'   => [$kmsId],
        ];

        foreach ($urls as $baseUrl) {
            try {
                $response = $this->httpClient->request('POST', $baseUrl . '/kms/wrap', [
                    'json' => $payload,
                    'cafile'     => $this->caPath,
                    'local_cert' => $this->certPath,
                    'local_pk'   => $this->keyPath,
                    'verify_peer' => true,
                    'verify_host' => true,
                    'timeout'     => 10,
                ]);
            } catch (\Throwable $e) {
                $this->logger->error(sprintf('KMS wrap HTTP error via %s: %s', $baseUrl, $e->getMessage()));
                continue;
            }

            if ($response->getStatusCode() !== 200) {
                $this->logger->error(sprintf('KMS wrap unexpected HTTP %d from %s', $response->getStatusCode(), $baseUrl));
                continue;
            }

            $body = json_decode($response->getContent(false), true);
            if (!is_array($body)) {
                $this->logger->error(sprintf('KMS wrap invalid JSON from %s', $baseUrl));
                continue;
            }

            // CHANGED: response is results map
            $results = $body['results'] ?? null;
            if (!is_array($results)) {
                $this->logger->error(sprintf('KMS wrap missing results from %s', $baseUrl));
                continue;
            }

            $entry = $results[$kmsId] ?? null;
            if (!is_array($entry) || !($entry['ok'] ?? false) || !is_string($entry['w_b64'] ?? null) || $entry['w_b64'] === '') {
                $this->logger->error(sprintf('KMS wrap no success for %s via %s', $kmsId, $baseUrl));
                continue;
            }

            return $entry['w_b64'];
        }

        return null;
    }
}