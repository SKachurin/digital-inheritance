<?php

namespace App\Service;

use App\Entity\Contact;
use App\Entity\VerificationToken;
use App\Repository\VerificationTokenRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

class VerificationEmailService
{
    public function __construct(
        private MailerInterface $mailer,
        private UrlGeneratorInterface $urlGenerator,
        private EntityManagerInterface $entityManager,
        private CryptoService $cryptoService,
        private VerificationTokenRepository $tokenRepository
    ) {}

    /**
     * @throws TransportExceptionInterface
     * @throws \SodiumException
     * @throws Exception
     * @throws \Exception
     */
    public function sendVerificationEmail(Contact $contact): void
    {
        $verificationToken = $this->tokenRepository->findOneBy(['contact' => $contact->getId()]);
        if ($verificationToken) {
            $this->tokenRepository->delete($verificationToken);
        }
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

        $emailAddress = $this->cryptoService->decryptData($contact->getValue());

        if (is_string($emailAddress)) {

            //TemplatedEmail with the Twig template
            $email = (new TemplatedEmail())
                ->from('info@thedigitalheir.com')
                ->to($emailAddress)
                ->subject('Email Verification - TheDigitalHeir')
                ->htmlTemplate('emails/verification_email.html.twig')
                ->context([
                    'verificationUrl' => $verificationUrl,
                    'emailAddress' => $emailAddress,
                    'year' => \Date('Y'),
                ])
                //a text template
                ->text('Click the link to verify your email address: '.$verificationUrl);

            $this->mailer->send($email);

        } else {
            throw new \Exception("Invalid email address.");
        }
    }
}
