<?php

namespace App\Command;

use App\Entity\Contact;

class ActionCreateCommand
{
    public function __construct(private Contact $contact)
    {
    }

    public function getContact(): Contact
    {
        return $this->contact;
    }
}
