<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\VerificationTokenRepository;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SocialVerificationController extends AbstractController
{
    /**
     * @throws Exception
     */
    public function verifySocial(
        string $token,
        VerificationTokenRepository $tokenRepository,
        EntityManagerInterface $entityManager
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
        $tokenRepository->delete($verificationToken);
        $entityManager->flush();


        $this->addFlash('success', 'Your Social has been verified.');
        return new RedirectResponse($this->generateUrl('user_home'));
    }
}
