<?php

namespace App\Service;

use App\Entity\Contact;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class SendWhatsAppService
{
    public function __construct(
        private CryptoService           $cryptoService,
        private HttpClientInterface     $client,
//        private LoggerInterface         $logger,
        private string                  $apiUrl,
        private string                  $apiToken
    )
    {}

    /**
     * @throws \SodiumException
     * @throws \Exception|TransportExceptionInterface
     */
    public function sendMessageWhatsApp(Contact $contact, ?string $message = ''): JsonResponse
    {
        $phone = $this->cryptoService->decryptData($contact->getValue());

//        $this->logger->error('3.4 CronBatchConsumer == $phone' . $phone);

        if (!is_string($phone)) {
            throw new \Exception("Invalid phone number.");
        }

        $phoneNumber = $contact->getCountryCode() . $phone;

        $url = $this->apiUrl . '/v3/message';

//        $this->logger->error('3.5 CronBatchConsumer == $url' . $url);

//        $this->logger->error('3.6 Sending WA API request', [
//            'url' => $this->apiUrl,
//            'token' => $this->apiToken,
//            '$message' => $message,
//            'phone' => $phoneNumber,
//        ]);

        try {
            $response = $this->client->request('POST', $url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiToken,
                    'Content-Type' => 'application/json',
                ],
                'body' => json_encode([
                    "channelId" => "058a7934-be60-4fa0-b943-61aab4818f23",
                    "chatType" => "whatsapp",
                    "text" => $message,
                    "chatId" => $phoneNumber,
                    "contentUri" => "",
                    "templateId" => ""
                ]),
            ]);

            $responseContent = json_decode($response->getContent(false), true);

//            $this->logger->error('3.6-0 WA API Response', [
//                'status_code' => $response->getStatusCode(),
//                'response' => $responseContent
//            ]);

            if ($response->getStatusCode() === 201 && isset($responseContent['messageId'])) {
                return new JsonResponse([
                    'success' => true,
                    'messageId' => $responseContent['messageId'],
                    'chatId' => $responseContent['chatId'],
                ], 201);
            }

            // Handle non-2xx
            return new JsonResponse([
                'error' => 'Failed to send WhatsApp message',
                'details' => $responseContent,
            ], $response->getStatusCode());

        } catch (\Exception $e) {

//            $this->logger->error(' 3.7 Error while sending WA API request', [
//                'exception' => $e->getMessage(),
//                'trace' => $e->getTraceAsString(),
//            ]);

            return new JsonResponse([
                'error' => 'Failed to call WhatsApp service',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
