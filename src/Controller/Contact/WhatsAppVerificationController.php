<?php

declare(strict_types=1);

namespace App\Controller\Contact;

use App\Event\ContactVerifiedEvent;
use App\Repository\VerificationTokenRepository;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

class WhatsAppVerificationController extends AbstractController
{
    public function __construct(
        private TranslatorInterface $translator
    ) {}

    /**
     * @throws Exception
     */
    public function verifyWa(
        string $token,
        VerificationTokenRepository $tokenRepository,
        EntityManagerInterface $entityManager,
        EventDispatcherInterface $eventDispatcher
    ): Response {
        $verificationToken = $tokenRepository->findOneBy(['token' => $token]);

        if (!$verificationToken || $verificationToken->isExpired()) {
            throw $this->createNotFoundException('Invalid or expired verification token.');
        }

        $contact = $verificationToken->getContact();

        // Mark the contact as verified
        $contact->setIsVerified(true);
        $entityManager->persist($contact);

        // Remove the token to prevent reuse
//        $tokenRepository->delete($verificationToken);  //would this fix the problem?
        $entityManager->flush();

        // Dispatch the event to create Actions
        $eventDispatcher->dispatch(new ContactVerifiedEvent($contact));

        $this->addFlash('success', $this->translator->trans('errors.flash.phone_verified'));
        return new RedirectResponse($this->generateUrl('user_home'));
    }
}
