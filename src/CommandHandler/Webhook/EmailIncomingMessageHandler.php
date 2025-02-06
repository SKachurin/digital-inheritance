<?php

namespace App\CommandHandler\Webhook;

use App\Service\MessageProcessorService;
use Psr\Log\LoggerInterface;
use App\Service\SendEmailService;

class EmailIncomingMessageHandler
{
    public function __construct(
        private readonly MessageProcessorService $messageProcessorService,
        private readonly SendEmailService $sendEmailService,
        private readonly LoggerInterface $logger
    )
    {}

    public function handle(array $payload): array
    {
        $this->logger->error('5.0 EmailIncomingMessageHandler STARTED', [
//            'payload' => $payload,
            'stripped-text' => $payload['stripped-text'],
            'body-plain' => $payload['body-plain'],

        ]);

        /**
         * body-plain string
         * The text version of the email.
         * This field is always present.
         * If the incoming message only has HTML body,
         * Mailgun will create a text representation for you.
         *
         * stripped-text string
         * The text version of the message
         * without quoted parts and signature block (if found)
         */
        $text = $payload['stripped-text'] ?? $payload['body-plain'];

        $this->messageProcessorService->processMessage(
            sender: $payload['sender'],
            text: $text,
            sendMessage: fn($contact, $message) => $this->sendEmailService->sendMessageEmail($contact, $message)
        );

        return ['status_code' => 200, 'payload' => ['success' => true]];
    }
}