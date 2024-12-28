<?php

namespace App\Service;

use App\Controller\PythonServiceController;
use App\Entity\Contact;
use App\Entity\VerificationToken;
use App\Repository\VerificationTokenRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class VerificationSocialService
{
    private UrlGeneratorInterface $urlGenerator;
    private EntityManagerInterface $entityManager;
    private CryptoService $cryptoService;
    private VerificationTokenRepository $tokenRepository;
    private PythonServiceController $pythonServiceController;

    public function __construct(
        UrlGeneratorInterface       $urlGenerator,
        EntityManagerInterface      $entityManager,
        CryptoService               $cryptoService,
        VerificationTokenRepository $tokenRepository,
        HttpClientInterface         $client,
        PythonServiceController $pythonServiceController
    )
    {
        $this->urlGenerator = $urlGenerator;
        $this->entityManager = $entityManager;
        $this->cryptoService = $cryptoService;
        $this->tokenRepository = $tokenRepository;
        $this->pythonServiceController = $pythonServiceController;
    }

    /**
     * @throws \SodiumException
     * @throws Exception
     * @throws \Exception
     */
    public function sendVerificationSocial(Contact $contact): array
    {
        $verificationToken = $this->tokenRepository->findOneBy(['contact' => $contact->getId()]);
        if ($verificationToken) {
            $this->tokenRepository->delete($verificationToken);
        }
        $token = new VerificationToken(
            $contact,
            'social',
            Uuid::v4()->toRfc4122(),
            new DateTimeImmutable('+1 hour')
        );

        $this->entityManager->persist($token);
        $this->entityManager->flush();

        $verificationUrl = $this->urlGenerator->generate('social_verification_route', [
            'token' => $token->getToken()
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $user = $this->cryptoService->decryptData($contact->getValue());
        if (!is_string($user)) {
            throw new \Exception("Invalid Telegram contact.");
        }

        $message = 'Thank you for registering! Please verify your Telegram by clicking on the following link: ' . $verificationUrl . ' or copy/paste it in browser';

        $response = $this->pythonServiceController->callPythonService([$user], $message);

        if ($response->getStatusCode() !== 200) {
            throw new \Exception('Failed to send verification social. Python service error: ' . $response->getContent());
        }

        // Decode the JSON content of the JsonResponse
        $responseData = json_decode($response->getContent(), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid JSON returned by Python service.');
        }

        return $responseData;
    }
}
