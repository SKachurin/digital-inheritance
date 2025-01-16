<?php

namespace App\CommandHandler\Webhook;

use App\Enum\ActionStatusEnum;
use App\Repository\ActionRepository;
use App\Repository\PipelineRepository;
use App\Service\SendWhatsAppService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class WazzupIncomingMessageHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ActionRepository $actionRepository,
        private readonly PipelineRepository $pipelineRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly SendWhatsAppService $sendWhatsAppService

    )
    {}

    public function handle(array $payload): array
    {
        // Just return 200 to Wazzup24
        if (isset($payload['test']) && $payload['test'] === true) {
            return [
                'status_code' => 200,
                'payload'     => ['test' => 'OK'],
            ];
        }

        if (!isset($payload['messages']) || !\is_array($payload['messages'])) {

            // If there's no 'messages' array, respond with 200 to avoid errors
            return [
                'status_code' => 200,
                'payload'     => ['success' => 'No messages to process'],
            ];
        }


        foreach ($payload['messages'] as $messageData) {
            $this->processSingleMessage($messageData);
        }

        return [
            'status_code' => 200,
            'payload' => [
                'success' => true,
            ],
        ];
    }

    /**
     * @throws TransportExceptionInterface
     * @throws \SodiumException
     */
    private function processSingleMessage(array $messageData): void
    {
        /*
         * According to docs: "messages" array might contain objects with:
         * - messageId
         * - chatId
         * - text
         */
        $chatId = $messageData['chatId'] ?? null;
        $text   = $messageData['text']   ?? null;

        if (!$chatId || !$text) {
//                'chatId' => $chatId ?? 'N/A',
//                'status' => 'Skipped - missing text/chatId',
//            log?
            return;
        }

        // 1) Find matching Action by chatId
        $action = $this->actionRepository->findOneBy(['wazzupChatId' => $chatId]);
        if (!$action) {

//                'chatId' => $chatId,
//                'status' => 'No matching Contact found',
//          log?
            return;
        }

        // 2) Check if this contact has a Customer + password logic, etc.
        $customer = $action->getCustomer();
        if (!$customer) {

//                'chatId' => $chatId,
//                'status' => 'Contact found but no associated Customer',
//          log?
            return;
        }

        // 3) Compare text to "OkayPassword"
        $okayPassword = $customer->getCustomerOkayPassword();
        $hashedText = $this->passwordHasher->hashPassword(
            $customer,
            $text
        );

        $contact = $action->getContact();

        if ($hashedText === $okayPassword) {

            //find && reset Pipeline
            $pipeline = $this->pipelineRepository->findOneBy(['customerId' => $customer]);

            $actionSequence = $pipeline->getActionSequence();
            $firstAction = array_filter($actionSequence, fn($a) => $a['position'] === 1);

            $pipeline->setActionType($firstAction['actionType']);
            $pipeline->setActionStatus(ActionStatusEnum::ACTIVATED);

            $this->entityManager->persist($pipeline);
            $this->entityManager->flush();

            //TODO as user logs in -- Lang choice loads from DB. So Lang from cookie should go to
            // DB after registration and after changing Lang in interface. And Lang from DB used to trans messages.
            $message = "It's nice to hear it. Resetting :-)";

            $this->sendWhatsAppService->sendMessageWhatsApp($contact, $message);

        } else {
            // Not matching password
            // send "Not the answer I expected"

            $message = "Not the answer I expected";

            //TODO as user logs in -- Lang choice loads from DB. So Lang from cookie should go to
            // DB after registration and after changing Lang in interface. And Lang from DB used to trans messages.


            $this->sendWhatsAppService->sendMessageWhatsApp($contact, $message);

//            $processedMessages[] = [
//                'chatId' => $chatId,
//                'status' => 'Message received, no password match',
//            ];
        }
    }
}
