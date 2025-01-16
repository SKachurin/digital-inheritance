<?php

namespace App\Controller\Webhook;

use App\CommandHandler\Webhook\WazzupIncomingMessageHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class WazzupWebhookController extends AbstractController
{
    public function __construct(
        private readonly WazzupIncomingMessageHandler $incomingMessageHandler
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);

        if (!\is_array($payload)) {
            return new JsonResponse(['error' => 'Invalid JSON'], 400);
        }

        // $this->logger->info('Incoming Wazzup message: ' . $request->getContent());

        try {
            $result = $this->incomingMessageHandler->handle($payload);

            $statusCode = $result['status_code'] ?? 200;
            $payload    = $result['payload'] ?? [];

            return new JsonResponse($payload, $statusCode);

        } catch (\Throwable $e) {
            return new JsonResponse([
                'error' => $e->getMessage()
            ], 500);
        }

    }
}

