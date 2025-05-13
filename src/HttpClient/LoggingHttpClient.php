<?php

namespace App\HttpClient;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;

final class LoggingHttpClient implements HttpClientInterface
{
    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly LoggerInterface $logger
    ) {}

    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        if (str_starts_with($url, 'https://api.cryptocloud.plus/v2/invoice/create')) {
            $this->logger->error('CryptoCloud invoice request', [
                'method'  => $method,
                'url'     => $url,
                'headers' => $options['headers'] ?? [],
                'json'    => $options['json'] ?? null,
            ]);
        }

        return $this->client->request($method, $url, $options);
    }

    public function stream($responses, float $timeout = null): ResponseStreamInterface
    {
        return $this->client->stream($responses, $timeout);
    }

    public function withOptions(array $options): static
    {
        // Optional: implement if needed by your application
        return new self($this->client->withOptions($options), $this->logger);
    }
}

