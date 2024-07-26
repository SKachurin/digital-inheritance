<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\VerificationTokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EmailVerificationController extends AbstractController
{
    #[Route('/verify-email/{token}', name: 'email_verification_route')]
    public function verifyEmail(
        string $token,
        VerificationTokenRepository $tokenRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $verificationToken = $tokenRepository->findOneBy(['token' => $token]);

        if (!$verificationToken || $verificationToken->isExpired()) {
            throw $this->createNotFoundException('Invalid or expired verification token.');
        }

        $customer = $verificationToken->getCustomer();

        // logic to mark the customer as verified
        // $customer->setEmailVerified(true);
        $entityManager->remove($verificationToken);
        $entityManager->flush();

        return new RedirectResponse($this->generateUrl('homepage'));
    }
}
