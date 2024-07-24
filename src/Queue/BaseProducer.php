<?php

namespace App\Queue;

use Doctrine\DBAL\Exception\ConnectionException;
use Exception;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\TransportException;
use Symfony\Component\Messenger\MessageBusInterface;

abstract class BaseProducer
{
    protected MessageBusInterface $messageBus;

    /**
     * @throws Exception
     */
    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    protected function baseProduce(BaseMessageInterface $message, array $stamps = []): Envelope
    {
        $envelope = (new Envelope($message))->with(...$stamps);
        $hasBaseInfoStamp = $envelope->last(BaseInfoStamp::class) !== null;

        if (!$hasBaseInfoStamp) {
            $envelope = $envelope->with(
                new BaseInfoStamp(static::class)
            );
        }

        try {
            $resultEnvelope = $this->messageBus->dispatch(
                $envelope
            );
        } catch (TransportException $e) {
            if (!$e->getPrevious() instanceof ConnectionException) {
                throw $e;
            }

            $reserveStamp = new DbFailedDeliveryStamp(
                $e->getCode(),
                $e->getMessage()
            );
            $reserveEnvelope = (new Envelope(
                new DbFailedDeliveryMessage($envelope, $reserveStamp)
            ))->with(
                new BaseInfoStamp(static::class)
            );

            $resultEnvelope = $this->messageBus->dispatch(
                $reserveEnvelope
            );
        }

        return $resultEnvelope;
    }
}