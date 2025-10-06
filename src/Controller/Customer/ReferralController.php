<?php

declare(strict_types=1);

namespace App\Controller\Customer;

use App\Entity\Customer;
use App\Repository\TransactionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ReferralController extends AbstractController
{
    public function __construct(
        private readonly TransactionRepository $transactionRepository,
        private readonly TokenStorageInterface $tokenStorage,
    ) {}

    public function __invoke(Request $request)
    {
        $token = $this->tokenStorage->getToken();
        $customer = $token?->getUser();

        if (!$customer instanceof Customer) {
            return $this->redirectToRoute('user_login');
        }

        $rewardsEarned = null;

        if(!$customer->getReferralCode()) {
            return $this->render('user/dashboard/referral.html.twig', [
                'rewardsEarned' => $rewardsEarned,
            ]);
        }

        if ($request->query->getBoolean('checkBalance')) {
            $rewardsEarned = $this->transactionRepository->getReferralBalance($customer);
        }

        return $this->render('user/dashboard/referral.html.twig', [
            'rewardsEarned' => $rewardsEarned,
        ]);
    }
}
