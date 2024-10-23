<?php

namespace App\Command;

use App\Entity\Contact;

class ActionCreateCommand
{
    private Contact $contact;

    public function __construct(Contact $contact)
    {
        $this->contact = $contact;
    }

    public function getContact(): Contact
    {
        return $this->contact;
    }
}
