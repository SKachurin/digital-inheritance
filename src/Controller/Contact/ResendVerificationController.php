<?php

declare(strict_types=1);

namespace App\Controller\Contact;

use App\Repository\ContactRepository;
use App\Repository\CustomerRepository;
use App\Service\VerificationEmailService;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ResendVerificationController extends AbstractController
{
    public function __construct(
        private ContactRepository            $repository,
        private CustomerRepository           $customerRepository,
        private VerificationEmailService     $verificationEmailService,
        private readonly TranslatorInterface $translator
    )
    {
    }

    /**
     * @throws TransportExceptionInterface
     * @throws Exception
     * @throws \SodiumException
     */
    public function resend(Request $request): Response
    {
        $email = $request->request->get('email');
        $customer = $this->customerRepository->findOneBy(['customerEmail' => $email]);

        if ($customer) {
            $contact = $this->repository->findOneBy(['customer' => $customer]);
            $lang = $customer->getLang() ?? 'en';
            $message = $this->translator->trans('messages.verification', [], 'messages', $lang);

            $this->verificationEmailService->sendVerificationEmail($contact, $message);

            $this->addFlash('success', $this->translator->trans('errors.flash.email_sent'));
        }

        return $this->redirectToRoute('wait');
    }
}
