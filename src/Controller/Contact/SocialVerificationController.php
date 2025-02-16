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

class SocialVerificationController extends AbstractController
{
    private readonly TranslatorInterface $translator;

    /**
     * @throws Exception
     */
    public function verifySocial(
        string $token,
        VerificationTokenRepository $tokenRepository,
        EntityManagerInterface $entityManager,
        EventDispatcherInterface $eventDispatcher
    ): Response {
        $verificationToken = $tokenRepository->findOneBy(['token' => $token]);

        if (!$verificationToken || $verificationToken->isExpired()) {

            $contact = $verificationToken ? $verificationToken->getContact() : null;

            if ($contact && $contact->getIsVerified()) {
                $this->addFlash('info', $this->translator->trans('errors.flash.social_verified_already'));
                return new RedirectResponse($this->generateUrl('user_home'));
            }

            throw $this->createNotFoundException('Invalid or expired verification token.');
        }

        $contact = $verificationToken->getContact();

        $contact->setIsVerified(true);
        $entityManager->persist($contact);

        // Remove the token later
//        $tokenRepository->delete($verificationToken);  //would this fix the problem?
        $entityManager->flush();

        // create Actions
        $eventDispatcher->dispatch(new ContactVerifiedEvent($contact));


        $this->addFlash('success', $this->translator->trans('errors.flash.social_verified')); //TODO Trans
        return new RedirectResponse($this->generateUrl('user_home'));
    }
}
