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
        private MailerInterface             $mailer,
        private UrlGeneratorInterface       $urlGenerator,
        private EntityManagerInterface      $entityManager,
        private VerificationTokenRepository $tokenRepository,
        private CryptoService               $cryptoService,
        private SendSocialService           $sendSocialService,
        private SendWhatsAppService         $sendWhatsAppService,
        private LoggerInterface             $logger,
    )
    {
    }

    /**
     * @throws Exception|TransportExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function notifyBeneficiary(Beneficiary $beneficiary, array $contacts, \DateTimeImmutable $now): bool
    {
        $beneficiaryFullName = $beneficiary->getBeneficiaryFullName()
            ? $this->cryptoService->decryptData($beneficiary->getBeneficiaryFullName())
            : '_unknown_';
        $customerFullName = $beneficiary->getCustomer()->getCustomerFullName()
                ? $this->cryptoService->decryptData($beneficiary->getCustomer()->getCustomerFullName())
                : '_unknown_';

        $message = sprintf(
            "Hey %s,\n\nPerson named %s has listed this contact as an emergency contact on The Digital Heir. Click the link to access the secure envelope:",
            $beneficiaryFullName, $customerFullName
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

                ContactTypeEnum::EMAIL => $this->sendEmailNotification($contact, $message, $accessUrl, $beneficiaryFullName, $customerFullName),
                ContactTypeEnum::PHONE => $this->sendWhatsAppNotification($contact, $message, $accessUrl),
//                ContactTypeEnum::MESSENGER
                ContactTypeEnum::SOCIAL => $this->sendSocialServiceNotification($contact, $message, $accessUrl),  // TODO add Telegram to Beneficiary fields

//                default => throw new Exception("Unsupported action type:" . $contact->getContactTypeEnum() ),
            };

        }

        return true; // ??????????
    }

    /**
     * @throws Exception|TransportExceptionInterface
     */
    private function sendEmailNotification(
        Contact $contact,
         string $message,
         string $accessUrl,
         string $beneficiaryFullName,
         string $customerFullName
    ): void
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
                    'beneficiaryFullName' => $beneficiaryFullName,
                    'customerFullName' => $customerFullName,
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

    /**
     * @throws \SodiumException
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    private function sendWhatsAppNotification(Contact $contact, string $message, string $accessUrl): void
    {
        $message = $message . ' '. $accessUrl;

        $this->logger->error('sendWhatsAppNotification Contact' . $contact->getId());

        $this->sendWhatsAppService->sendMessageWhatsApp($contact, $message);

//        $phone = $this->cryptoService->decryptData($contact->getValue());
//
////        $this->logger->error('3.4 CronBatchConsumer == $phone' . $phone);
//
//        if (!is_string($phone)) {
//            throw new \Exception("Invalid phone number.");
//        }
//
//        $phoneNumber = $contact->getCountryCode() . $phone;
//
//        $url = $this->apiUrl . '/v3/message';
//
//
//        try {
//            $this->client->request('POST', $url, [
//                'headers' => [
//                    'Authorization' => 'Bearer ' . $this->apiToken,
//                    'Content-Type' => 'application/json',
//                ],
//                'body' => json_encode([
//                    "channelId" => "058a7934-be60-4fa0-b943-61aab4818f23",
//                    "chatType" => "whatsapp",
//                    "text" => $message,
//                    "chatId" => $phoneNumber,
//                    "contentUri" => "",
//                    "templateId" => ""
//                ]),
//            ]);
//
//        } catch (\Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface $e) {
//
//        }
    }

    /**
     * @throws \SodiumException
     */
    private function sendSocialServiceNotification(Contact $contact, string $message, string $accessUrl): void
    {
        $message = $message . ' ' . $accessUrl;

        $this->logger->error('sendSocialServiceNotification Contact' . $contact->getId());

        $this->sendSocialService->sendMessageSocial($contact, $message);

    }
}