<?php

declare(strict_types=1);

namespace App\CommandHandler\Customer\Delete;

class CustomerDeleteInputDto
{
    public function __construct(
        public string $contactType = 'email',
    ) {}

    public function getContactType(): string
    {
        return $this->contactType;
    }
}