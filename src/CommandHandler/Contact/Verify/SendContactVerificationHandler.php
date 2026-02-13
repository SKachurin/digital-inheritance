<?php

declare(strict_types=1);

namespace App\CommandHandler\Contact\Verify;

use App\Entity\Contact;
use App\Entity\Customer;
use App\Message\SendContactVerificationMessage;
use App\Repository\ContactRepository;
use App\Service\VerificationEmailService;
use App\Service\VerificationSocialService;
use App\Service\VerificationWhatsAppService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsMessageHandler]
final class SendContactVerificationHandler
{
    public function __construct(
        private readonly ContactRepository           $contactRepository,
        private readonly VerificationEmailService    $verificationEmailService,
        private readonly VerificationWhatsAppService $verificationWhatsAppService,
        private readonly VerificationSocialService   $verificationSocialService,
        private readonly TranslatorInterface         $translator,
        private readonly LoggerInterface             $logger,
    ) {}

    public function __invoke(SendContactVerificationMessage $message): void
    {
        $contact = $this->contactRepository->getOneBy(['id' => $message->getContactId()]);
        if (!$contact instanceof Contact) {
            return;
        }

        if ($contact->getIsVerified()) {
            return;
        }

        $customer = $contact->getCustomer();
        $lang = ($customer instanceof Customer ? ($customer->getLang() ?? 'en') : 'en');

        $text = $this->translator->trans('messages.verification', [], 'messages', $lang);

        try {
            switch ($contact->getContactTypeEnum()) {
                case 'email':
                    $this->verificationEmailService->sendVerificationEmail($contact, $text);
                    break;

                case 'phone':
                    $this->verificationWhatsAppService->sendVerificationWhatsApp($contact, $text);
                    break;

                case 'social':
                    $this->verificationSocialService->sendVerificationSocial($contact, $text);
                    break;

                default:
                    // Unknown type: do nothing
                    return;
            }
        } catch (\Throwable $e) {
            // Do not throw (otherwise messenger retries may spam external providers)
            $this->logger->error('Failed to send contact verification', [
                'contactId' => $contact->getId(),
                'type' => $contact->getContactTypeEnum(),
                'error' => $e->getMessage(),
            ]);
        }
    }
}