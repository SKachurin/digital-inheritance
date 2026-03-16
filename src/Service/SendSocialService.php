<?php

namespace App\Service;

use App\Controller\PythonServiceController;
use App\Entity\Contact;
use App\Enum\CustomerSocialAppEnum;
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
    public function sendMessageSocial(Contact $contact, ?string $message = ''): JsonResponse // it's Check not message
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

    /**
     * @throws \SodiumException
     * @throws \Exception
     */
    public function sendRealMessageSocial(Contact $contact, ?string $message = ''): JsonResponse
    {
        $customer = $contact->getCustomer();

        if (!$customer) {
            return new JsonResponse([
                'error' => 'Contact has no customer.',
            ], 400);
        }

        $socialApp = $customer->getCustomerSocialApp();

        if ($socialApp instanceof CustomerSocialAppEnum) {
            $socialApp = $socialApp->value;
        }

        return match ($socialApp) {
            CustomerSocialAppEnum::TELEGRAM->value => $this->sendTelegramMessage($contact, $message),
            default => new JsonResponse([
                'error' => sprintf('Unsupported social app: %s', (string) $socialApp),
            ], 400),
        };
    }

    /**
     * @throws \SodiumException
     * @throws \Exception
     */
    private function sendTelegramMessage(Contact $contact, ?string $message = ''): JsonResponse
    {
        $user = $this->cryptoService->decryptData($contact->getValue());

        if (!is_string($user) || $user === '') {
            throw new \Exception('Invalid Telegram contact.');
        }

        try {
            return $this->pythonServiceController->callPythonService([$user], $message);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Failed to call Telegram service',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

}
