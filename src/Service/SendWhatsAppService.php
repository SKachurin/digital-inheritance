<?php

namespace App\Service;

use App\Entity\Contact;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SendWhatsAppService
{
    private CryptoService $cryptoService;
    private HttpClientInterface $client;
    private string $apiUrl;
    private string $apiToken;

    public function __construct(
        CryptoService           $cryptoService,
        HttpClientInterface     $client,
        string                  $apiUrl,
        string                  $apiToken
    )
    {
        $this->cryptoService = $cryptoService;
        $this->client = $client;
        $this->apiUrl = $apiUrl;
        $this->apiToken = $apiToken;
    }

    /**
     * @throws \SodiumException
     * @throws \Exception|TransportExceptionInterface
     */
    public function sendMessageWhatsApp(Contact $contact, ?string $message = ''): JsonResponse
    {
        $phone = $this->cryptoService->decryptData($contact->getValue());
        if (!is_string($phone)) {
            throw new \Exception("Invalid phone number.");
        }

        $phoneNumber = $contact->getCountryCode() . $phone;


        $url = $this->apiUrl . '/v3/message';

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

            return new JsonResponse([
                'error' => 'Failed to call WhatsApp service',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
