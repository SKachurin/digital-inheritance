<?php

namespace App\Service;

use App\Entity\Contact;
use App\Entity\VerificationToken;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Uid\Uuid;

class VerificationEmailService
{
    private MailerInterface $mailer;
    private UrlGeneratorInterface $urlGenerator;
    private EntityManagerInterface $entityManager;
    private CryptoService $cryptoService;

    public function __construct(
        MailerInterface $mailer,
        UrlGeneratorInterface $urlGenerator,
        EntityManagerInterface $entityManager,
        CryptoService $cryptoService
    ) {
        $this->mailer = $mailer;
        $this->urlGenerator = $urlGenerator;
        $this->entityManager = $entityManager;
        $this->cryptoService = $cryptoService;
    }

    /**
     * @throws TransportExceptionInterface
     * @throws \SodiumException
     */
    public function sendVerificationEmail(Contact $contact): void
    {
        $token = new VerificationToken(
            $contact,
            'email',
            Uuid::v4()->toRfc4122(),
            new DateTimeImmutable('+1 hour')
        );

        $this->entityManager->persist($token);
        $this->entityManager->flush();

        $verificationUrl = $this->urlGenerator->generate('email_verification_route', [
            'token' => $token->getToken()
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $email = $this->cryptoService->decryptData($contact->getValue());

        $email = (new Email())
            ->from('info@thedigitalheir.com')
            ->to($email)
            ->subject('Email Verification TheDigitalHeir')
            ->html('<p>Thank you for registering! Please verify your email by clicking on the following link: <a href="' . $verificationUrl . '">Verify Email</a></p>')
            ->text('Click the link to verify your email address: '.$verificationUrl);

        $this->mailer->send($email);
    }
}
