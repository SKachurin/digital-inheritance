<?php

namespace App\CommandHandler\Webhook;

use App\Service\MessageProcessorService;
use App\Service\SupportEmailForwarderService;
use Psr\Log\LoggerInterface;
use App\Service\SendEmailService;

class EmailIncomingMessageHandler
{
    public function __construct(
        private readonly MessageProcessorService $messageProcessorService,
        private readonly SendEmailService $sendEmailService,
        private readonly SupportEmailForwarderService $supportForwarder,
        private readonly LoggerInterface $logger
    )
    {}

    public function handle(array $payload): array
    {
//        $this->logger->error('5.0 EmailIncomingMessageHandler STARTED', [
////            'payload' => $payload,
//            'stripped-text' => $payload['stripped-text'],
//            'body-plain' => $payload['body-plain'],
//
//        ]);

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
        $sender = $payload['sender'] ?? '';
        $recipient = $payload['recipient'] ?? '';

        $this->logger->error('5.0 EmailIncomingMessageHandler STARTED', [
            'payload' => $payload,
            'recipient' => $payload['recipient'] ?? 'recipient_missing',
            'sender' => $payload['sender'] ?? 'sender_missing',
            'stripped-text' => $payload['stripped-text'],
            'body-plain' => $payload['body-plain'],
        ]);

//        foreach ($payload as $key => $value) {
//            $this->logger->error("Payload field: $key", ['value' => $value]);
//        }

        if (str_contains(strtolower($recipient), 'support')) {
            $this->supportForwarder->forwardSupportEmail($sender, $payload['subject'], $text);

            return ['status_code' => 200, 'payload' => ['success' => true]];
        }

        $this->messageProcessorService->processMessage(
            sender: $sender,
            text: $text,
            sendMessage: fn($contact, $message) => $this->sendEmailService->sendMessageEmail($contact, $message)
        );

        return ['status_code' => 200, 'payload' => ['success' => true]];
    }
}