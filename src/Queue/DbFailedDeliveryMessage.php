<?php

namespace App\Queue;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\AckStamp;

class DbFailedDeliveryMessage implements BaseMessageInterface
{
    private Envelope $failedEnvelope;
    private DbFailedDeliveryStamp $failedStamp;

    public function __construct(Envelope $failedEnvelope, DbFailedDeliveryStamp $failedStamp)
    {
        $this->failedEnvelope = $failedEnvelope->withoutStampsOfType(AckStamp::class);
        $this->failedStamp = $failedStamp;
    }

    public function getFailedEnvelope(): Envelope
    {
        return $this->failedEnvelope;
    }

    public function getFailedStamp(): DbFailedDeliveryStamp
    {
        return $this->failedStamp;
    }
}