<?php

namespace App\CommandHandler\Webhook;

use App\Entity\Contact;
use App\Entity\Customer;
use App\Repository\ContactRepository;
use App\Repository\CustomerRepository;
use Doctrine\ORM\EntityManagerInterface;

class WazzupIncomingMessageHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ContactRepository      $contactRepository,
        private readonly CustomerRepository     $customerRepository,
        // Possibly other services you need...
    )
    {
    }

    public function handle(array $payload): void
    {
        // Example: Wazzup might send multiple messages in the `data` array
        if (!isset($payload['data']) || !\is_array($payload['data'])) {
            return; // or throw exception
        }

        foreach ($payload['data'] as $messageData) {
            $this->processSingleMessage($messageData);
        }
    }

    private function processSingleMessage(array $messageData): void
    {
        /*
         * Example fields you might see:
         * - chatId (like "whatsapp:+11112223344")
         * - text (the actual message from the user)
         * - messageId (unique ID from Wazzup)
         */

        $chatId = $messageData['chatId'] ?? null;
        $text = $messageData['text'] ?? null;

        if (!$chatId || !$text) {
            // you might want to log or return, can't do much
            return;
        }

        // 1) Find a matching Contact in DB by the chatId
        //    Because you stored `wazzupChatId` or might parse phone from "chatId"
        /** @var Contact|null $contact */
        $contact = $this->contactRepository->findOneBy(['wazzupChatId' => $chatId]);

        if (!$contact) {
            // We did not find an existing contact with that chatId
            // => maybe parse phone or create a new contact on the fly, etc.
            // For example:
            // $parsedPhone = $this->extractPhone($chatId);
            // $contact = $this->contactRepository->findOneByPhone($parsedPhone);

            // If we still can't find them, you might create a brand-new Contact
            // or skip. Up to your business logic.

            return;
        }

        // 2) We have a Contact => get the Customer
        $customer = $contact->getCustomer();
        if (!$customer) {
            // Possibly skip if no associated Customer
            return;
        }

        // 3) Compare user’s text with the "OkayPassword"
        //    Example: the Customer should respond with a password
        $okayPassword = $customer->getCustomerOkayPassword();

        if ($text === $okayPassword) {
            // Mark them verified, or whatever your domain logic is
            $this->markContactVerified($contact);
        }

        // Optionally, you might create an Action or record a "message received"
        // in your system. For example:
        // $this->createAction($customer, $text);

        // ...
    }

    private function markContactVerified(Contact $contact): void
    {
        $contact->setIsVerified(true);
        $this->entityManager->persist($contact);
        $this->entityManager->flush();
    }
}
