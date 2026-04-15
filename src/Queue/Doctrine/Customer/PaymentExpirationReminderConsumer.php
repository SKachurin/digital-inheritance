<?php

declare(strict_types=1);

namespace App\Queue\Doctrine\Customer;

use App\Entity\Contact;
use App\Message\PaymentExpirationReminderMessage;
use App\Repository\ContactRepository;
use App\Repository\CustomerRepository;
use App\Service\SendEmailService;
use App\Service\SendSocialService;
use App\Service\SendWhatsAppService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsMessageHandler]
class PaymentExpirationReminderConsumer
{
    public function __construct(
        private readonly CustomerRepository $customerRepository,
        private readonly ContactRepository $contactRepository,
        private readonly SendEmailService $sendEmailService,
        private readonly SendWhatsAppService $sendWhatsAppService,
        private readonly SendSocialService $sendSocialService,
        private readonly TranslatorInterface $translator,
    ) {
    }

    /**
     * @throws TransportExceptionInterface
     * @throws \SodiumException
     */
    public function __invoke(PaymentExpirationReminderMessage $message): void
    {
        $customer = $this->customerRepository->find($message->getCustomerId());

        if (!$customer) {
            return;
        }

        $verifiedContacts = $this->contactRepository->findVerifiedCustomerContactsOrdered($customer->getId());

        if ($verifiedContacts === []) {
            return;
        }

        $contact = $this->pickReminderContact($verifiedContacts, $message->getDaysLeft());
        $lang = $customer->getLang() ?: 'en';

        $text = $this->translator->trans(
            'messages.payment_expiring',
            ['%days%' => $message->getDaysLeft()],
            'messages',
            $lang
        );

        $this->sendToContact($contact, $text);
    }


    /**
     *
     * @param Contact[] $contacts
     *
     * 3 days left -> first verified contact
     * 2 days left -> second verified contact, fallback to first
     * 1 day left -> third verified contact, fallback to second/first
     */
    private function pickReminderContact(array $contacts, int $daysLeft): Contact
    {
        $targetIndex = match ($daysLeft) {
            3 => 0,
            2 => 1,
            1 => 2,
            default => 0,
        };

        $safeIndex = min($targetIndex, count($contacts) - 1);

        return $contacts[$safeIndex];
    }

    /**
     * @throws TransportExceptionInterface
     * @throws \SodiumException
     */
    private function sendToContact(Contact $contact, string $message): void
    {
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
}