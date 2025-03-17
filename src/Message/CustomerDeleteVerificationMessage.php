<?php

declare(strict_types=1);

namespace App\Message;

class CustomerDeleteVerificationMessage
{
    public function __construct(
        public readonly int $customerId,
        public readonly array $verifiedContacts
    ) {}

    public function getCustomerId(): int
    {
        return $this->customerId;
    }

    public function getVerifiedContacts(): array
    {
        return $this->verifiedContacts;
    }
}