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

class SocialVerificationController extends AbstractController
{
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
                $this->addFlash('info', 'Your social contact has already been verified.');
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


        $this->addFlash('success', 'Your Social has been verified.');
        return new RedirectResponse($this->generateUrl('user_home'));
    }
}
