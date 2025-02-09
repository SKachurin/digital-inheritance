<?php

namespace App\Service;

use App\Entity\Beneficiary;
use App\Entity\Contact;
use App\Entity\VerificationToken;
use App\Enum\ContactTypeEnum;
use App\Repository\VerificationTokenRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

class BeneficiaryNotificationService
{
    public function __construct(
        private MailerInterface $mailer,
        private UrlGeneratorInterface $urlGenerator,
        private EntityManagerInterface $entityManager,
        private VerificationTokenRepository $tokenRepository,
        private CryptoService $cryptoService,
        private LoggerInterface $logger,
    ) {}


    /**
     * @throws Exception|TransportExceptionInterface
     */
    public function notifyBeneficiary(Beneficiary $beneficiary, array $contacts, \DateTimeImmutable $now): bool
    {
        $message = sprintf(
            "Hey %s,\n\nPerson named %s has listed this contact as an emergency contact on The Digital Heir. Click the link to access the secure envelope:",
            $beneficiary->getBeneficiaryFullName()
                ? $this->cryptoService->decryptData($beneficiary->getBeneficiaryFullName())
                : '_unknown_',
            $beneficiary->getCustomer()->getCustomerFullName()
                ? $this->cryptoService->decryptData($beneficiary->getCustomer()->getCustomerFullName())
                : '_unknown_'
        );

        foreach ($contacts as $contact) {

            $verificationToken = $this->tokenRepository->findOneBy(['contact' => $contact]);
            if ($verificationToken) {
                $this->tokenRepository->delete($verificationToken);
            }

            $this->logger->error('$contact: '. $contact->getId());

            $token = new VerificationToken(
                $contact,
                'email',
                Uuid::v4()->toRfc4122(),
                new DateTimeImmutable('+1 week')
            );

            $this->entityManager->persist($token);
            $this->entityManager->flush();

            $this->logger->error('$token: '. $token->getId());

            $accessUrl = $this->urlGenerator->generate('beneficiary_access_note', [
                'token' => $token->getToken()
            ], UrlGeneratorInterface::ABSOLUTE_URL);

            match (ContactTypeEnum::tryFrom($contact->getContactTypeEnum()) ) {

                ContactTypeEnum::EMAIL => $this->sendEmailNotification($contact, $message, $token, $accessUrl),
                ContactTypeEnum::PHONE,
                ContactTypeEnum::MESSENGER => $this->logger->error('ContactTypeEnum::MESSENGER'),
                ContactTypeEnum::SOCIAL => $this->logger->error('ContactTypeEnum::SOCIAL'),

//                default => throw new Exception("Unsupported action type:" . $contact->getContactTypeEnum() ),
            };

        }

        return true; // ??????????
    }

    /**
     * @throws Exception|TransportExceptionInterface
     */
    private function sendEmailNotification(Contact $contact, string $message, VerificationToken $token, string $accessUrl): void
    {
        $emailAddress = $this->cryptoService->decryptData($contact->getValue());

        $this->logger->error('sendEmailNotification $emailAddress: ' .$emailAddress);

        if (is_string($emailAddress)) {
            $email = (new TemplatedEmail())
                ->from('info@thedigitalheir.com')
                ->to($emailAddress)
                ->subject('Access to Secure Envelope - TheDigitalHeir')
                ->htmlTemplate('emails/beneficiary_email.html.twig')
                ->context([
                    'beneficiaryFullName' => $contact->getValue(),
                    'customerFullName' => $contact->getValue(),
                    'accessUrl' => $accessUrl,
                    'emailAddress' => $emailAddress,
                    'year' => \Date('Y'),
                ])
                ->text($message . $accessUrl);

            $this->mailer->send($email);
        } else {
            throw new Exception("Invalid email address.");
        }
    }
}