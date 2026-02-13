<?php

declare(strict_types=1);

namespace App\Message;

final class SendContactVerificationMessage
{
    public function __construct(
        private readonly int $contactId,
    ) {}

    public function getContactId(): int
    {
        return $this->contactId;
    }
}