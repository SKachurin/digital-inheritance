<?php

namespace App\Service;

use App\Controller\PythonServiceController;
use App\Entity\Contact;
use Symfony\Component\HttpFoundation\JsonResponse;

class SendSocialService
{
    public function __construct(
        private CryptoService           $cryptoService,
        private PythonServiceController $pythonServiceController
    )
    {
    }

    /**
     * @throws \SodiumException
     * @throws \Exception
     */
    public function sendMessageSocial(Contact $contact, ?string $message = ''): JsonResponse
    {
        $user = $this->cryptoService->decryptData($contact->getValue());

        if (!is_string($user)) {
            throw new \Exception("Invalid Telegram contact.");
        }

        try {
            $response = $this->pythonServiceController->callPythonService([$user], $message);

        } catch (\Exception $e) {

            return new JsonResponse([
                'error' => 'Failed to call Python service',
                'message' => $e->getMessage()
            ], 500);
        }

        return $response;
    }
}
