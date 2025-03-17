<?php

declare(strict_types=1);

namespace App\Controller\Customer;

use App\Entity\Customer;
use App\Service\CustomerDeletionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

class CustomerDeleteVerificationController extends AbstractController
{
    public function __construct(
        private readonly CustomerDeletionService     $customerDeletionService,
        private readonly TranslatorInterface         $translator,
    )
    {
    }

    public function __invoke(string $token): Response
    {
        /** @var  Customer $customer */
        $customer = $this->getUser();

        if (!$customer instanceof Customer) {
            $message = 'error';
            return $this->render('user/deleteVerification.html.twig', [
                'status' => $message,
            ]);
        }

        $lang = $customer?->getLang() ?? 'en';

        $result = $this->customerDeletionService->processToken($token, $customer);

        switch ($result) {
            case 'token':
            case 'customer':
                $message = $this->translator->trans('messages.incorrect_delete_token', [], 'messages', $lang);

                return $this->render('user/deleteVerification.html.twig', [
                    'status' => $message,
                ]);
            case 'success':
                $message = $this->translator->trans('messages.correct_delete_token', [], 'messages', $lang);
                return $this->render('user/deleteVerification.html.twig', [
                    'status' => $message,
                ]);
            default:
                $message = 'error';
                return $this->render('user/deleteVerification.html.twig', [
                    'status' => $message,
                ]);
        }
    }
}