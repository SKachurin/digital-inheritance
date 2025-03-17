<?php

declare(strict_types=1);

namespace App\Message;

class CustomerDeletedMessage
{
    public function __construct(
        public readonly int $customerId,
        public readonly string $contactType
    ) {}

    public function getCustomerId(): int
    {
        return $this->customerId;
    }
    public function getContactType(): string
    {
        return $this->contactType;
    }
}