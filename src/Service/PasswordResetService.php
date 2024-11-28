<?php

namespace App\Service;

use App\Entity\VerificationToken;
use App\Repository\ContactRepository;
use App\Repository\CustomerRepository;
use App\Repository\VerificationTokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Uid\Uuid;
use DateTimeImmutable;

class PasswordResetService
{
    private EntityManagerInterface $entityManager;
    private MailerInterface $mailer;
    private UrlGeneratorInterface $urlGenerator;
    private CryptoService $cryptoService;
    private VerificationTokenRepository $tokenRepository;
    private ContactRepository $contactRepository;
    private CustomerRepository $customerRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        MailerInterface $mailer,
        UrlGeneratorInterface $urlGenerator,
        CryptoService $cryptoService,
        VerificationTokenRepository $tokenRepository,
        ContactRepository $contactRepository,
        CustomerRepository $customerRepository,
    ) {
        $this->entityManager = $entityManager;
        $this->mailer = $mailer;
        $this->urlGenerator = $urlGenerator;
        $this->cryptoService = $cryptoService;
        $this->tokenRepository = $tokenRepository;
        $this->contactRepository = $contactRepository;
        $this->customerRepository = $customerRepository;
    }

    /**
     * Sends a password reset email to the user associated with the provided email.
     * @throws \SodiumException
     */
    public function sendPasswordResetEmail(string $email): void
    {
        $customer = $this->customerRepository->findOneBy([
            'customerEmail' => $email
        ]);

        $contacts = $this->contactRepository->findBy([
            'customer' => $customer,
            'contactTypeEnum' => 'email',
            'isVerified' => true
            ]);

        if (!$contacts) {
            return;
        }

        // Remove existing verification tokens for this contact
        foreach ($contacts as $contact) {
            $existingTokens = $this->tokenRepository->findBy([
                'contact' => $contact,
            ]);
            if ($existingTokens) {
                foreach ($existingTokens as $existingToken) {
                    $this->entityManager->remove($existingToken);
                }
            }
        }
        $this->entityManager->flush();

        // Generate a new verification token
        $token = new VerificationToken(
            $contacts[0],
            'password',
            Uuid::v4()->toRfc4122(),
            new DateTimeImmutable('+1 hour')
        );

        $this->entityManager->persist($token);
        $this->entityManager->flush();

        // Generate the password reset URL
        $resetUrl = $this->urlGenerator->generate('password_reset_reset', [
            'token' => $token->getToken(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        // Decrypt the email to send the message
        foreach ($contacts as $contact) {
            $emailAddress = $this->cryptoService->decryptData($contact->getValue());

            $emailMessage = (new Email())
                ->from('info@thedigitalheir.com')
                ->to($emailAddress)
                ->subject('Password Reset Request')
                ->html('<p>If you asked for this please reset your password by clicking <a href="' . $resetUrl . '">here</a>. Otherwise, just ignore this message.</p>')
                ->text('Reset your password: ' . $resetUrl);

            try {
                $this->mailer->send($emailMessage);
            } catch (TransportExceptionInterface $e) {
                // Log or handle
            }
        }
    }
}
