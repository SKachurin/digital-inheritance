<?php

namespace App\Queue;

use DateTimeImmutable;
use Exception;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Messenger\Stamp\StampInterface;

class BaseInfoStamp implements StampInterface
{
    private string $traceUuid;
    private string $originService;
    private string $originAction;
    private string $producedAt;

    /**
     * @throws Exception
     */
    public function __construct(string $originAction, ?string $traceUuid = null)
    {
        if (empty($_ENV['SERVICE_NAME'])) {
            throw new Exception("Отсутствует ENV переменная 'SERVICE_NAME'.");
        }

        $this->traceUuid = $traceUuid ?? Uuid::v4()->toRfc4122();
        $this->originService = $_ENV['SERVICE_NAME'];
        $this->originAction = $originAction;
        $this->producedAt = (new DateTimeImmutable())->format('c');
    }

    public function getTraceUuid(): string
    {
        return $this->traceUuid;
    }

    public function getOriginService(): string
    {
        return $this->originService;
    }

    public function getOriginAction(): string
    {
        return $this->originAction;
    }

    public function getProducedAt(): string
    {
        return $this->producedAt;
    }
}