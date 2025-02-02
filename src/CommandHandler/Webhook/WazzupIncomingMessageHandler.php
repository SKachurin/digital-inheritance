<?php
//
//namespace App\CommandHandler\Webhook;
//
//use App\Enum\ActionStatusEnum;
//use App\Repository\ActionRepository;
//use App\Repository\PipelineRepository;
//use App\Service\SendWhatsAppService;
//use Doctrine\ORM\EntityManagerInterface;
//use Psr\Log\LoggerInterface;
//use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
//
//class WazzupIncomingMessageHandler
//{
//    public function __construct(
//        private readonly EntityManagerInterface $entityManager,
//        private readonly ActionRepository $actionRepository,
//        private readonly PipelineRepository $pipelineRepository,
//        private readonly SendWhatsAppService $sendWhatsAppService,
//        private readonly LoggerInterface $logger
//    )
//    {}
//
//    /**
//     * @throws TransportExceptionInterface
//     * @throws \SodiumException
//     */
//    public function handle(array $payload): array
//    {
//        // Just return 200 to Wazzup24
//        if (isset($payload['test']) && $payload['test'] === true) {
//            return [
//                'status_code' => 200,
//                'payload'     => ['test' => 'OK'],
//            ];
//        }
//
//        if (!isset($payload['messages']) || !\is_array($payload['messages'])) {
//
//            // If there's no 'messages' array, respond with 200 to avoid errors
//            return [
//                'status_code' => 200,
//                'payload' => [
//                    'success' => true,
//                ],
//            ];
//        }
//
//        $this->logger->error('5 WazzupIncomingMessageHandler ', [
//            'message' => $payload['messages'],
//        ]);
//
//        foreach ($payload['messages'] as $messageData) {
//            $this->processSingleMessage($messageData);
//        }
//
//        return [
//            'status_code' => 200,
//            'payload' => [
//                'success' => true,
//            ],
//        ];
//    }
//
//    /**
//     * @throws TransportExceptionInterface
//     * @throws \SodiumException
//     */
//    private function processSingleMessage(array $messageData): void
//    {
//
//        $chatId = $messageData['chatId'] ?? null;
//        $text   = $messageData['text']   ?? null;
//        $inbound = $messageData['status']; // == 'inbound'
//
//        if (!$chatId || !$text || $inbound !== 'inbound') {
//
//            $this->logger->error('6 !=inbound ');
//
//            return; // Nothing to process
//        }
//
//        // 1) Find matching Action by chatId
//        $action = $this->actionRepository->findOneBy(['chatId' => $chatId]);
//        if (!$action) {
//
//            $this->logger->error('6.1 processSingleMessage ', [
//                'error' => 'error',
//            ]);
//            return;
//        }
//
//        // 2) Check if this contact has a Customer + password logic, etc.
//        $customer = $action->getCustomer();
//        if (!$customer) {
//
//            $this->logger->error('6.2 processSingleMessage ', [
//                'error' => 'error',
//            ]);
//            return;
//        }
//
//        // 3) Compare text to "OkayPassword"
//        $okayPassword = $customer->getCustomerOkayPassword();
//
//        $contact = $action->getContact();
//
//        if (password_verify($text, $okayPassword)) {
//
//            //find && reset Pipeline
//            $pipeline = $this->pipelineRepository->findOneBy(['customer' => $customer]);
//
//            if (!$pipeline) {
//
//                $this->logger->error('6.3 processSingleMessage ', [
//                    'error' => 'error',
//                ]);
//                return;
//            }
//
//            $actionSequence = $pipeline->getActionSequence();
//            $firstAction = array_filter($actionSequence, fn($a) => $a['position'] === 1);
//
//            $pipeline->setActionType($firstAction['actionType']);
//            $pipeline->setActionStatus(ActionStatusEnum::ACTIVATED);
//
//            $this->entityManager->persist($pipeline);
//            $this->entityManager->flush();
//
//            //TODO as user logs in -- Lang choice loads from DB. So Lang from cookie should go to
//            // DB after registration and after changing Lang in interface. And Lang from DB used to trans messages.
//
//            $this->logger->error('6.4 Resetting ', [
//                'error' => 'Resetting',
//            ]);
//
//            $message = "It's nice to hear it. Resetting :-)";
//
//            $this->sendWhatsAppService->sendMessageWhatsApp($contact, $message);
//
//        } else {
//            // Not matching password
//            $message = "Not the answer I expected";
//
//            //TODO as user logs in -- Lang choice loads from DB. So Lang from cookie should go to
//            // DB after registration and after changing Lang in interface. And Lang from DB used to trans messages.
//
//
//            $this->sendWhatsAppService->sendMessageWhatsApp($contact, $message);
//
//            $this->logger->error('6.5 processSingleMessage ', [
//                'chatId' => $chatId,
//                'status' => 'Message received, no password match',
//            ]);
//        }
//    }
//}
namespace App\CommandHandler\Webhook;

use App\Enum\ActionStatusEnum;
use App\Repository\ActionRepository;
use App\Repository\PipelineRepository;
use App\Service\SendWhatsAppService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class WazzupIncomingMessageHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ActionRepository $actionRepository,
        private readonly PipelineRepository $pipelineRepository,
        private readonly SendWhatsAppService $sendWhatsAppService,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * @throws TransportExceptionInterface
     * @throws \SodiumException
     */
    public function handle(array $payload): array
    {
        $this->logger->error('5.0 WazzupIncomingMessageHandler STARTED', [
            'payload' => $payload,
        ]);

        // If test message, return early
        if (isset($payload['test']) && $payload['test'] === true) {
            $response = [
                'status_code' => 200,
                'payload'     => ['test' => 'OK'],
            ];
            $this->logger->error('5.1 WazzupIncomingMessageHandler Returning Test Response', [
                'response' => $response
            ]);
            return $response;
        }

        if (!isset($payload['messages']) || !\is_array($payload['messages'])) {
            $response = [
                'status_code' => 200,
                'payload' => ['success' => true],
            ];
            $this->logger->error('5.2 WazzupIncomingMessageHandler NO messages found, Returning 200', [
                'response' => $response
            ]);
            return $response;
        }

        $this->logger->error('5.3 WazzupIncomingMessageHandler Processing Messages', [
            'messages' => $payload['messages'],
        ]);

        foreach ($payload['messages'] as $messageData) {
            $this->processSingleMessage($messageData);
        }

        $response = [
            'status_code' => 200,
            'payload' => ['success' => true],
        ];
        $this->logger->error('5.4 WazzupIncomingMessageHandler Response Sent', [
            'response' => $response,
        ]);

        return $response;
    }

    /**
     * @throws TransportExceptionInterface
     * @throws \SodiumException
     */
    private function processSingleMessage(array $messageData): void
    {
        $this->logger->error('6.0 processSingleMessage STARTED', [
            'messageData' => $messageData
        ]);

        $chatId = $messageData['chatId'] ?? null;
        $text   = $messageData['text']   ?? null;
        $inbound = $messageData['status'] ?? '';

        if (!$chatId || !$text || $inbound !== 'inbound') {
            $this->logger->error('6.1 processSingleMessage INVALID MESSAGE', [
                'chatId' => $chatId,
                'text' => $text,
                'inbound' => $inbound
            ]);
            return;
        }

        // 1) Find matching Action by chatId
        $action = $this->actionRepository->findOneBy(['chatId' => $chatId]);
        if (!$action) {
            $this->logger->error('6.2 processSingleMessage NO ACTION FOUND', [
                'chatId' => $chatId
            ]);
            return;
        }

        // 2) Check if this contact has a Customer + password logic, etc.
        $customer = $action->getCustomer();
        if (!$customer) {
            $this->logger->error('6.3 processSingleMessage NO CUSTOMER FOUND', [
                'chatId' => $chatId
            ]);
            return;
        }

        // 3) Compare text to "OkayPassword"
        $okayPassword = $customer->getCustomerOkayPassword();
        $contact = $action->getContact();

        if (password_verify($text, $okayPassword)) {
            // Find & reset Pipeline
            $pipeline = $this->pipelineRepository->findOneBy(['customer' => $customer]);

            if (!$pipeline) {
                $this->logger->error('6.4 processSingleMessage NO PIPELINE FOUND', [
                    'customerId' => $customer->getId()
                ]);
                return;
            }

            $actionSequence = $pipeline->getActionSequence();
            $firstAction = array_filter($actionSequence, fn($a) => $a['position'] === 1);


            $this->logger->error('6.1-0 processSingleMessage', [
                '$firstAction' => $firstAction
            ]);

            if (!empty($firstAction)) {
                $fa = reset($firstAction); // or $fa = current($firstAction);
                // Now we do $fa['actionType'] safely
                $pipeline->setActionType($fa['actionType']);

                if (!isset($fa['actionType'])) {
                    $this->logger->error('6.1-1  No "actionType" found in $fa');
                    return;
                }
                $pipeline->setActionType($fa['actionType']);

                $pipeline->setActionType($firstAction['actionType']);
                $pipeline->setActionStatus(ActionStatusEnum::ACTIVATED);


                $this->entityManager->persist($pipeline);
                $this->entityManager->flush();

                $this->logger->error('6.5 processSingleMessage PIPELINE RESET', [
                    'pipelineId' => $pipeline->getId()
                ]);

                $message = "It's nice to hear it. Resetting :-)";
                $this->sendWhatsAppService->sendMessageWhatsApp($contact, $message);


            } else {
                $this->logger->error('6.1-0 processSingleMessage no item in $firstAction');
            }

        } else {
            // Incorrect password case
            $message = "Not the answer I expected";
            $this->sendWhatsAppService->sendMessageWhatsApp($contact, $message);

            $this->logger->error('6.7 processSingleMessage INCORRECT PASSWORD', [
                'chatId' => $chatId,
                'status' => 'Message received, no password match'
            ]);
        }
    }
}

