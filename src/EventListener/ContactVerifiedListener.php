<?php

namespace App\EventListener;

use Symfony\Component\Messenger\MessageBusInterface;
use App\Command\ActionCreateCommand;

class ContactVerifiedListener
{
    public function __construct(private MessageBusInterface $commandBus)
    {
    }

    public function __invoke($event): void
    {
        $contact = $event->getContact();  // Use event to pass Contact

        $this->commandBus->dispatch(new ActionCreateCommand($contact));


    }
}

