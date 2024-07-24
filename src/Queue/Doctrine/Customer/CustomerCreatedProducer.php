<?php

declare(strict_types=1);

namespace App\Queue\Doctrine\Customer;

use App\Queue\BaseProducer;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\StampInterface;

class CustomerCreatedProducer extends BaseProducer
{
    /**
     * @param StampInterface[] $stamps
     */
    public function produce(CustomerCreatedMessage $message, array $stamps = []): Envelope
    {
        return parent::baseProduce($message, $stamps);
    }
}
