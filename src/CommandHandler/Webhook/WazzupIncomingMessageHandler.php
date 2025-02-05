<?php

namespace App\CommandHandler\Webhook;

use App\Service\MessageProcessorService;
use App\Service\SendWhatsAppService;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class WazzupIncomingMessageHandler
{
    public function __construct(
        private readonly MessageProcessorService $messageProcessorService,
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

        if (isset($payload['test']) && $payload['test'] === true) {
            return ['status_code' => 200, 'payload' => ['test' => 'OK']];
        }

        //NO messages found, Returning 200
        if (!isset($payload['messages']) || !\is_array($payload['messages'])) {
            return ['status_code' => 200,'payload' => ['success' => true]];
        }

//        $this->logger->error('5.3 WazzupIncomingMessageHandler Processing Messages', [
//            'messages' => $payload['messages'],
//        ]);

        foreach ($payload['messages'] as $messageData) {
            $this->processSingleMessage($messageData);
        }

        return ['status_code' => 200, 'payload' => ['success' => true]];
    }

    /**
     * @throws TransportExceptionInterface
     * @throws \SodiumException
     */
    private function processSingleMessage(array $messageData): void
    {

        if (!isset($messageData['chatId'], $messageData['text']) || $messageData['status'] !== 'inbound') {
            return;
        }

        $this->messageProcessorService->processMessage(
            sender: $messageData['chatId'],
            text: $messageData['text'],
            sendMessage: fn($contact, $message) => $this->sendWhatsAppService->sendMessageWhatsApp($contact, $message)
        );
    }
}

