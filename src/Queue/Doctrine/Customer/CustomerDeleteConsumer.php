<?php

declare(strict_types=1);

namespace App\Queue\Doctrine\Customer;

use App\Entity\VerificationToken;
use App\Message\CustomerDeleteVerificationMessage;
use App\Repository\CustomerRepository;
use App\Entity\Contact;
use App\Repository\VerificationTokenRepository;
use App\Service\SendEmailService;
use App\Service\SendWhatsAppService;
use App\Service\SendSocialService;
use DateTimeImmutable;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsMessageHandler]
class CustomerDeleteConsumer
{
    public function __construct(
        private readonly SendEmailService            $sendEmailService,
        private readonly SendWhatsAppService         $sendWhatsAppService,
        private readonly SendSocialService           $sendSocialService,
        private readonly VerificationTokenRepository $tokenRepository,
        private readonly UrlGeneratorInterface       $urlGenerator,
        private readonly EntityManagerInterface      $entityManager,
        private readonly TranslatorInterface         $translator,
        private readonly CustomerRepository          $customerRepository,
    )
    {
    }

    /**
     * @throws TransportExceptionInterface
     * @throws \SodiumException
     * @throws Exception
     * @throws ORMException
     */
    public function __invoke(CustomerDeleteVerificationMessage $message): void
    {
        $customerId = $message->getCustomerId();
        $verifiedContacts = $message->getVerifiedContacts();
        $lang = $this->customerRepository->findOneBy(['id' => $customerId])?->getLang() ?? 'en';

        foreach ($verifiedContacts as $contact) {
            $this->sendVerification($contact, $lang);
        }
    }

    /**
     * @throws TransportExceptionInterface
     * @throws \SodiumException
     * @throws Exception
     * @throws ORMException
     */
    private function sendVerification(Contact $contact, string $lang): void
    {
        $contact = $this->entityManager->getReference(Contact::class, $contact->getId());

        $verificationToken = $this->tokenRepository->findOneBy(['contact' => $contact]);
        if ($verificationToken) {
            $this->tokenRepository->delete($verificationToken);
        }

        $token = new VerificationToken(
            $contact,
            $contact->getContactTypeEnum(),
            Uuid::v4()->toRfc4122(),
            new DateTimeImmutable('+1 hour')
        );

        $this->entityManager->persist($token);
        $this->entityManager->flush();

        $verificationUrl = $this->urlGenerator->generate('customer_delete_verification_route', [
            'token' => $token->getToken()
        ], UrlGeneratorInterface::ABSOLUTE_URL);


        $message = $this->createMessage($lang, $verificationUrl);

        switch ($contact->getContactTypeEnum()) {
            case 'email':
                $this->sendEmailService->sendMessageEmail($contact, $message);
                break;
            case 'phone':
            case 'messenger':
                $this->sendWhatsAppService->sendMessageWhatsApp($contact, $message);
                break;
            case 'social':
                $this->sendSocialService->sendMessageSocial($contact, $message);
                break;
            default:
                throw new \LogicException('Unknown contact type: ' . $contact->getContactTypeEnum());
        }
    }


    private function createMessage(string $lang, string $verificationUrl): string
    {
        if (gettype($verificationUrl) !== 'string') {
            throw new \LogicException('Unknown contact type: ');
        }

        return $this->translator->trans(
            'messages.confirm_delete_account',
            [
                '%link%' => $verificationUrl
            ],
            'messages',
            $lang
        );
    }
}
