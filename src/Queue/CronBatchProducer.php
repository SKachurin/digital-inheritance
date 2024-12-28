<?php

namespace App\Queue;

use App\Message\CronBatchMessage;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\StampInterface;

class CronBatchProducer extends BaseProducer
{
    /**
     * @param int[]          $customerIds
     * @param StampInterface[] $stamps
     */
    public function produce(array $customerIds, array $stamps = []): Envelope
    {
        $message = new CronBatchMessage($customerIds);

        return parent::baseProduce($message, $stamps);
    }
}