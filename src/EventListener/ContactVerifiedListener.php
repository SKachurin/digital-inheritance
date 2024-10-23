<?php

namespace App\EventListener;

use App\Entity\Contact;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use App\Command\ActionCreateCommand;

class ContactVerifiedListener
{
    private MessageBusInterface $commandBus;

    public function __construct(MessageBusInterface $commandBus)
    {
        $this->commandBus = $commandBus;
    }

    public function __invoke($event): void
    {
        $contact = $event->getContact();  // Use event to pass Contact

        $envelope = $this->commandBus->dispatch(new ActionCreateCommand($contact));
        $handledStamp = $envelope->last(HandledStamp::class);

        if (!$handledStamp) {
            throw new UnprocessableEntityHttpException('500 internal error (CommandBus not responding).');
        }

    }
}

