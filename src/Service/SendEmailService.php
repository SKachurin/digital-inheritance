<?php

namespace App\Service;

use App\Controller\PythonServiceController;
use App\Entity\Contact;

class SendEmailService
{
//    private CryptoService $cryptoService;
//    private PythonServiceController $pythonServiceController;
//
//    public function __construct(
//        CryptoService               $cryptoService,
//        PythonServiceController $pythonServiceController
//    )
//    {
//        $this->cryptoService = $cryptoService;
//        $this->pythonServiceController = $pythonServiceController;
//    }
//
//    /**
//     * @throws \SodiumException
//     * @throws \Exception
//     */
//    public function sendMessageSocial(Contact $contact, string $message): string
//    {
//
//        $user = $this->cryptoService->decryptData($contact->getValue());
//        if (!is_string($user)) {
//            throw new \Exception("Invalid Telegram contact.");
//        }
//
//        $message = 'R u okay?';
//
//        try {
//            $this->pythonServiceController->callPythonService([$user], $message);
//
//        } catch (\Exception $e) {
//
//            return $e->getMessage();
//        }
//
//        return 'sent successfully';
//    }
}
