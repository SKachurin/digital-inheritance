<?php

declare(strict_types=1);

namespace App\CommandHandler\Customer\Delete;

use App\Message\CustomerDeletedMessage;
use App\Message\CustomerDeleteVerificationMessage;
use App\Repository\ContactRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
class CustomerDeletedHandler
{
    public function __construct(
        private readonly ContactRepository   $contactRepository,
        private readonly MessageBusInterface $commandBus
    )
    {
    }

    public function __invoke(CustomerDeletedMessage $message): void
    {
        $customerId = $message->getCustomerId();
        $contactType = $message->getContactType();

        $verifiedContacts = $this->contactRepository->findVerifiedContactsByType($customerId, $contactType);

        if (empty($verifiedContacts)) {
            throw new \RuntimeException("No verified contacts found for customer $customerId with type $contactType.");
        }

        // Dispatch the next async message with contacts
        $this->commandBus->dispatch(new CustomerDeleteVerificationMessage($customerId, $verifiedContacts));
    }
}