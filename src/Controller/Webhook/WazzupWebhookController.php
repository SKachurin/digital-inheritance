<?php

namespace App\Controller\Webhook;

use App\CommandHandler\Webhook\WazzupIncomingMessageHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class WazzupWebhookController extends AbstractController
{
    public function __construct(
        private readonly WazzupIncomingMessageHandler $incomingMessageHandler
    ) {
    }

    /**
     * @Route("/api/wazzup", name="wazzup_webhook", methods={"POST"})
     */
    public function __invoke(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);

        if (!\is_array($payload)) {
            return new JsonResponse(['error' => 'Invalid JSON'], 400);
        }

        // Example log or debug
        // $this->logger->info('Incoming Wazzup message: ' . $request->getContent());

        try {
            // Pass to your handler for business logic
            $this->incomingMessageHandler->handle($payload);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'error' => $e->getMessage()
            ], 500);
        }

        return new JsonResponse(['success' => true], 200);
    }
}

