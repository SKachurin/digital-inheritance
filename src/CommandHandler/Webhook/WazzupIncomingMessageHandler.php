<?php

namespace App\CommandHandler\Webhook;

use App\Enum\ActionStatusEnum;
use App\Repository\ActionRepository;
use App\Repository\PipelineRepository;
use App\Service\SendWhatsAppService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class WazzupIncomingMessageHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ActionRepository $actionRepository,
        private readonly PipelineRepository $pipelineRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly SendWhatsAppService $sendWhatsAppService,
        private readonly LoggerInterface $logger
    )
    {}

    /**
     * @throws TransportExceptionInterface
     * @throws \SodiumException
     */
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

        $this->logger->error('5 WazzupIncomingMessageHandler ', [
            'message' => $payload['messages'],
        ]);

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

        $chatId = $messageData['chatId'] ?? null;
        $text   = $messageData['text']   ?? null;
        $inbound = $messageData['status']; // == 'inbound'

        if (!$chatId || !$text || $inbound !== 'inbound') {

            $this->logger->error('6 processSingleMessage ', [
                '$inbound' => $inbound ?? 'undefined',
                '$chatId' => $chatId,
                '$text' => $text,
            ]);

            return; // Nothing to process
        }

        // 1) Find matching Action by chatId
        $action = $this->actionRepository->findOneBy(['chat_id' => $chatId]);
        if (!$action) {

            $this->logger->error('6.1 processSingleMessage ', [
                'error' => 'error',
            ]);
            return;
        }

        // 2) Check if this contact has a Customer + password logic, etc.
        $customer = $action->getCustomer();
        if (!$customer) {

            $this->logger->error('6.2 processSingleMessage ', [
                'error' => 'error',
            ]);
            return;
        }

        // 3) Compare text to "OkayPassword"
        $okayPassword = $customer->getCustomerOkayPassword();

//        $hashedText = $this->passwordHasher->hashPassword(
//            $customer,
//            $text
//        );

        $contact = $action->getContact();


        if (password_verify($text, $okayPassword)) {

            //find && reset Pipeline
            $pipeline = $this->pipelineRepository->findOneBy(['customer' => $customer]);

            if (!$pipeline) {

                $this->logger->error('6.3 processSingleMessage ', [
                    'error' => 'error',
                ]);
                return;
            }

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
            $message = "Not the answer I expected";

            //TODO as user logs in -- Lang choice loads from DB. So Lang from cookie should go to
            // DB after registration and after changing Lang in interface. And Lang from DB used to trans messages.


            $this->sendWhatsAppService->sendMessageWhatsApp($contact, $message);

            $this->logger->error('6.3 processSingleMessage ', [
                'chatId' => $chatId,
                'status' => 'Message received, no password match',
            ]);
        }
    }
}
