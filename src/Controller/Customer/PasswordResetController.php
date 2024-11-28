<?php

namespace App\Controller\Customer;

use App\Form\Type\PasswordRestoreType;
use App\Form\Type\PasswordResetType;
use App\Repository\VerificationTokenRepository;
use App\Service\PasswordResetService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
// Include necessary classes
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class PasswordResetController extends AbstractController
{
    private PasswordResetService $passwordResetService;

    public function __construct(
        PasswordResetService $passwordResetService
    ) {
        $this->passwordResetService = $passwordResetService;
    }

    /**
     * @throws \SodiumException
     */
    public function restore(Request $request): Response
    {
        $form = $this->createForm(PasswordRestoreType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $email */
            $email = $form->get('email')->getData();

            // Use the service to send the password reset email
            $this->passwordResetService->sendPasswordResetEmail($email);

            // Always show success message
            $this->addFlash('success', "If an account with that email exists, a password reset link has been sent to it's verified emails.");

            return $this->redirectToRoute('user_login');
        }

        return $this->render('user\restorePassword.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    public function reset(
        Request $request,
        string $token,
        VerificationTokenRepository $tokenRepository,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        // Find the verification token of type 'password'
        $verificationToken = $tokenRepository->findOneBy([
            'token' => $token,
            'type' => 'password',
        ]);

        if (!$verificationToken || $verificationToken->isExpired()) {
            $this->addFlash('danger', 'This password reset link is invalid or has expired.');

            return $this->redirectToRoute('user_login');
        }

        $form = $this->createForm(PasswordResetType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $contact = $verificationToken->getContact();
            $customer = $contact->getCustomer();

            $newPassword = $form->get('plainPassword')->getData();

            $hashedPassword = $passwordHasher->hashPassword($customer, $newPassword);
            $customer->setPassword($hashedPassword);

            // Remove the token to prevent reuse
            $entityManager->remove($verificationToken);
            $entityManager->flush();

            $this->addFlash('success', 'Your password has been reset successfully.');

            return $this->redirectToRoute('user_login');
        }

        return $this->render('user\resetPassword.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
