<?php

namespace App\Message;

use App\Queue\BaseMessageInterface;

class CronBatchMessage implements BaseMessageInterface
{
    /**
     * @var int[]
     */
    private array $customerIds;

    public function __construct(array $customerIds)
    {
        $this->customerIds = $customerIds;
    }

    /**
     * @return int[]
     */
    public function getCustomerIds(): array
    {
        return $this->customerIds;
    }
}
