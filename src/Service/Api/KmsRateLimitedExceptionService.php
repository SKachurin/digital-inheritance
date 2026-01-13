<?php

namespace App\Service\Api;

class KmsRateLimitedExceptionService extends \RuntimeException
{
    public function __construct(
        private readonly int $retryAfterSeconds,
        string               $message = 'KMS rate limited',
        int                  $code = 429,
        ?\Throwable          $previous = null
    )
    {
        parent::__construct($message, $code, $previous);
    }

    public function getRetryAfterSeconds(): int
    {
        return $this->retryAfterSeconds;
    }
}
