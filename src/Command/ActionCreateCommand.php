<?php

namespace App\Command;

use App\Entity\Contact;

class ActionCreateCommand
{
    public function __construct(public readonly int $contactId)
    {
    }

    public function getContactId(): int
    {
        return $this->contactId;
    }
}
