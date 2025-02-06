<?php

namespace App\Controller\Webhook;

use App\CommandHandler\Webhook\EmailIncomingMessageHandler;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class EmailWebhookController extends AbstractController
{
    public function __construct(
        private readonly EmailIncomingMessageHandler $incomingMessageHandler,
        private readonly LoggerInterface $logger
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);

        $this->logger->error('4 email_webhook', [
            '$payload' => $payload,
            '$request' => $request
        ]);

        if (!\is_array($payload)) {
            return new JsonResponse(['error' => 'Invalid JSON'], 400);
        }

//        $this->logger->error('4.1 email_webhook', [
//            'message' => $request->getContent(),
//        ]);

        try {
            $result = $this->incomingMessageHandler->handle($payload);

            $statusCode = $result['status_code'] ?? 200;
            $payload = $result['payload'] ?? [];

            return new JsonResponse($payload, $statusCode);

        } catch (\Throwable $e) {

//            $this->logger->error('EmailWebhookController error', [
//                'exception' => $e->getMessage(),
//                'trace'     => $e->getTraceAsString(),
//            ]);

            return new JsonResponse([
                'error' => $e->getMessage()
            ], 500);
        }
    }
}