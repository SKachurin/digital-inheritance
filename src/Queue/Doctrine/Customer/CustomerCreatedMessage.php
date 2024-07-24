<?php

namespace App\Queue\Doctrine\Customer;

use App\CommandHandler\Customer\Create\CustomerCreateInputDto;
use App\Queue\BaseMessageInterface;

class CustomerCreatedMessage implements BaseMessageInterface
{
    private CustomerCreateInputDto $customerCreateInputDto;

    public function __construct(CustomerCreateInputDto $customerCreateInputDto)
    {
        $this->customerCreateInputDto = $customerCreateInputDto;
    }

    public function getCustomerCreateInputDto(): CustomerCreateInputDto
    {
        return $this->customerCreateInputDto;
    }
}
