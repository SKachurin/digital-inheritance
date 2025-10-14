<?php

declare(strict_types=1);

namespace App\Controller\Customer;

use App\Entity\Customer;
use App\Repository\CustomerRepository;
use App\Repository\TransactionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ReferralController extends AbstractController
{
    public function __construct(
        private readonly TransactionRepository $transactionRepository,
        private readonly CustomerRepository $customerRepository,
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
        $currentPayout = null;
        $customerReferralCount_2nd = null;

        if(!$customer->getReferralCode()) {
            return $this->render('user/dashboard/referral.html.twig', [
                'rewardsEarned' => $rewardsEarned,
                'currentPayout' => $currentPayout,
                'customerReferralCount_2nd' => $customerReferralCount_2nd,
            ]);
        }

        // handle ?checkBalance=1
        if ($request->query->getBoolean('checkBalance')) {
            $rewardsEarned = $this->transactionRepository->getReferralBalance($customer);
        }

        // handle ?currentPayout=1
        if ($request->query->getBoolean('currentPayout')) {
            $currentPayout = $this->transactionRepository->getReferralPayout($customer);
        }

        // handle ?customerReferralCount_2nd=1
        if ($request->query->getBoolean('customerReferralCount_2nd')) {
            $customerReferralCount_2nd = $this->customerRepository->countSecondLevelReferrals($customer);
        }

        return $this->render('user/dashboard/referral.html.twig', [
            'rewardsEarned' => $rewardsEarned,
            'currentPayout' => $currentPayout,
            'customerReferralCount_2nd' => $customerReferralCount_2nd,
        ]);
    }
}
