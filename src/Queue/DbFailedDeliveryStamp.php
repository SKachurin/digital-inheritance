<?php

namespace App\Queue;

use Symfony\Component\Messenger\Stamp\StampInterface;

class DbFailedDeliveryStamp implements StampInterface
{
    private ?int $errorCode;
    private ?string $message;

    public function __construct(?int $errorCode = null, ?string $message = null)
    {
        $this->errorCode = $errorCode;
        $this->message = $message;
    }

    /**
     * @return int|null
     */
    public function getErrorCode(): ?int
    {
        return $this->errorCode;
    }

    /**
     * @return string|null
     */
    public function getMessage(): ?string
    {
        return $this->message;
    }
}