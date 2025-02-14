<?php

namespace App\Service;

use App\Entity\Contact;
use App\Entity\VerificationToken;
use App\Repository\VerificationTokenRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class VerificationWhatsAppService
{
    public function __construct(
        private MailerInterface             $mailer,
        private UrlGeneratorInterface       $urlGenerator,
        private EntityManagerInterface      $entityManager,
        private CryptoService               $cryptoService,
        private VerificationTokenRepository $tokenRepository,
        private HttpClientInterface         $client,
        private string                      $apiUrl,
        private string                      $apiToken,
    )
    {
    }

    /**
     * @throws \SodiumException
     * @throws Exception
     * @throws \Exception
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface|DecodingExceptionInterface
     */
    public function sendVerificationWhatsApp(Contact $contact, string $message): void //TODO return something
    {
        $verificationToken = $this->tokenRepository->findOneBy(['contact' => $contact->getId()]);
        if ($verificationToken) {
            $this->tokenRepository->delete($verificationToken);
        }
        $token = new VerificationToken(
            $contact,
            'phone',
            Uuid::v4()->toRfc4122(),
            new DateTimeImmutable('+1 hour')
        );

        $this->entityManager->persist($token);
        $this->entityManager->flush();

        $verificationUrl = $this->urlGenerator->generate('wa_verification_route', [
            'token' => $token->getToken()
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $phone = $this->cryptoService->decryptData($contact->getValue());
        if (!is_string($phone)) {
            throw new \Exception("Invalid phone number.");
        }

        $phoneNumber = $contact->getCountryCode() . $phone;

        if (is_string($phone)) {
            $url = $this->apiUrl . '/v3/message';
            $response = $this->client->request('POST', $url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiToken,
                    'Content-Type' => 'application/json',
                ],
                'body' => [
                    "channelId" => "058a7934-be60-4fa0-b943-61aab4818f23",
                    "chatType"=> "whatsapp",
                    "text" => $message . $verificationUrl,
                    "chatId"=> $phoneNumber,
                    "contentUri" => "",
                    "templateId" => ""
                ],
            ]);

            try {
                $responseContent = $response->toArray();
            } catch (\Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface $e) {
                // Handle the 4xx error here
                $statusCode = $response->getStatusCode();
                $errorContent = $response->getContent(false); //raw response
                throw new \Exception("WhatsApp failed (HTTP $statusCode). Response: " . $errorContent);
            }


            if (!isset($responseContent['messageId'])) {
                throw new \Exception('Failed to send OTP via WhatsApp. Response: ' . json_encode($responseContent));
            }

        } else {
            throw new \Exception("Invalid phone address.");
        }
    }
}
