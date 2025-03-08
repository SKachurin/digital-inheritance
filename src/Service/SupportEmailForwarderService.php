<?php

namespace App\Service;

use App\Controller\PythonServiceController;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class SupportEmailForwarderService
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private PythonServiceController  $pythonServiceController,
        private string                   $admin_tg
    )
    {
    }

    public function forwardSupportEmail(string $sender, string $text): void
    {
        $forwardedMessage = sprintf(
            "New support email from: %s\n\n%s",
            $sender,
            $text
        );

        $user = $this->admin_tg;

        if (!is_string($user)) {

            $this->logger->info('Forwarding support email', [
                'Exception' => "Invalid Telegram contact."
            ]);

            return;
        }

        try {
            $response = $this->pythonServiceController->callPythonService([$user], $forwardedMessage);

        } catch (\Exception $e) {
            $this->logger->info('Forwarding support email', [
                'Exception' => $e,
                '$forwardedMessage' => $forwardedMessage,
            ]);
        }

    }
}
