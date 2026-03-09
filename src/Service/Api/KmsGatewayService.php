<?php

declare(strict_types=1);

namespace App\Service\Api;

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
     * @return array<string,string> map kms_id => raw INNER blob bytes (variable length)
     * @throws KmsRateLimitedExceptionService
     */
    public function unwrapInners(int $userId, string $h_b64, string $answerFp, array $replicas): array
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
                    'cafile'      => $this->caPath,
                    'local_cert'  => $this->certPath,
                    'local_pk'    => $this->keyPath,
                    'verify_peer' => true,
                    'verify_host' => true,
                    'timeout'     => 10,
                ]);
            } catch (\Throwable $e) {
                $this->logger->error(sprintf('KMS unwrap HTTP error via %s: %s', $baseUrl, $e->getMessage()));
                continue;
            }

            $status = $response->getStatusCode();
            $raw = $response->getContent(false);

            if ($status === 429) {
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


            $body = json_decode($raw, true);
            if (!is_array($body)) {
                $this->logger->error(sprintf('KMS unwrap invalid JSON from %s', $baseUrl));
                continue;
            }

            $innersOut = [];

            $inners_b64 = $body['deks_b64'] ?? null; // keep gateway field name as-is
            if (!is_array($inners_b64)) {
                $this->logger->error(sprintf('KMS unwrap missing/invalid deks_b64 from %s', $baseUrl));
                continue;
            }

            foreach ($inners_b64 as $kmsId => $innerB64) {
                if (!is_string($kmsId) || $kmsId === '') continue;
                if (!is_string($innerB64) || $innerB64 === '') continue;

                $inner = base64_decode($innerB64, true);
                if ($inner === false || $inner === '') {
                    $this->logger->error(sprintf('KMS unwrap malformed INNER for %s via %s', $kmsId, $baseUrl));
                    continue;
                }

                $innersOut[$kmsId] = $inner;
            }

            return $innersOut;
        }

        return [];
    }

    public function wrapInner(
        int $userId,
        int $kmsNumber,
        string $inner_b64,
        string $h_b64,
        string $answerFp
    ): ?string {
        $kmsId = 'kms' . $kmsNumber;

        $urls = $this->resolveGatewayUrlsForReplicas([
            ['kms_id' => $kmsId, 'w_b64' => 'x'] // just to resolve URLs; or create a dedicated resolver
        ]);
        if (!$urls) {
            return null;
        }

        $payload = [
            'user_id'   => $userId,
            'kms_id'    => $kmsId,
            'inner_b64' => $inner_b64,
            'h_b64'     => $h_b64,
            'answer_fp' => $answerFp,
        ];

        foreach ($urls as $baseUrl) {
            try {
                $resp = $this->httpClient->request('POST', $baseUrl . '/kms/wrap', [
                    'json'        => $payload,
                    'cafile'      => $this->caPath,
                    'local_cert'  => $this->certPath,
                    'local_pk'    => $this->keyPath,
                    'verify_peer' => true,
                    'verify_host' => true,
                    'timeout'     => 10,
                ]);
            } catch (\Throwable $e) {
                $this->logger->error(sprintf('KMS wrap HTTP error via %s: %s', $baseUrl, $e->getMessage()));
                continue;
            }

            $status = $resp->getStatusCode();
            $raw    = $resp->getContent(false);

            if ($status !== 200) {
                $this->logger->error('KMS wrap unexpected HTTP', ['status' => $status, 'baseUrl' => $baseUrl, 'raw' => $raw]);
                continue;
            }

            $body = json_decode($raw, true);
            $wB64 = is_array($body) ? ($body['w_b64'] ?? null) : null;

            if (!is_string($wB64) || $wB64 === '') {
                $this->logger->error('KMS wrap missing w_b64', ['baseUrl' => $baseUrl, 'body' => $body]);
                continue;
            }

            return $wB64;
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

    public function wrapPayloadsViaGateway(
        int $userId,
        string $payload_b64,   // this is INNER encoded as base64
        string $h_b64,
        string $answerFp,
        array $kmsIds          // ['kms1','kms3']
    ): array {
        $urls = $this->resolveGatewayUrlsForReplicas(array_map(
            static fn(string $id) => ['kms_id' => $id, 'w_b64' => 'x'],
            $kmsIds
        ));
        if (!$urls) {
            throw new \RuntimeException('No KMS gateway URLs resolved.');
        }

        $payload = [
            'user_id'   => $userId,
            'dek_b64'   => $payload_b64,     // CONTRACT UNCHANGED (dek_b64), SEMANTICS CHANGED (INNER)
            'h_b64'     => $h_b64,
            'answer_fp' => $answerFp,
            'kms_ids'   => array_values($kmsIds),
        ];

        foreach ($urls as $baseUrl) {
            try {
                $resp = $this->httpClient->request('POST', $baseUrl . '/kms/wrap', [
                    'json'        => $payload,
                    'cafile'      => $this->caPath,
                    'local_cert'  => $this->certPath,
                    'local_pk'    => $this->keyPath,
                    'verify_peer' => true,
                    'verify_host' => true,
                    'timeout'     => 10,
                ]);
            } catch (\Throwable $e) {
                $this->logger->error(sprintf('KMS wrap HTTP error via %s: %s', $baseUrl, $e->getMessage()));
                continue;
            }

            $status = $resp->getStatusCode();
            $raw    = $resp->getContent(false);

            if ($status !== 200) {
                $this->logger->error('KMS wrap unexpected HTTP', ['status'=>$status,'baseUrl'=>$baseUrl,'raw'=>$raw]);
                continue;
            }

            $body = json_decode($raw, true);
            $results = is_array($body) ? ($body['results'] ?? null) : null;

            if (!is_array($results)) {
                $this->logger->error('KMS wrap missing results', ['baseUrl'=>$baseUrl,'body'=>$body]);
                continue;
            }

            $out = [];
            foreach ($results as $kmsId => $r) {
                if (!is_array($r)) continue;
                if (($r['ok'] ?? false) !== true) continue;
                $w = $r['w_b64'] ?? null;
                if (is_string($w) && $w !== '') {
                    $out[$kmsId] = $w;
                }
            }

            return $out;
        }

        return [];
    }
}