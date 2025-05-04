<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\Customer;
use App\Enum\CustomerPaymentStatusEnum;
use DateTimeImmutable;
//use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Twig\Environment;

class CustomerPaymentStatusListener
{
    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
        private readonly Environment           $twig
//        private LoggerInterface                $logger
    )
    {
    }

    /**
     * Checks customer's payment status and trial period,
     * and exposes it to Twig as 'customerPaymentStatus'.
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $route = $request->attributes->get('_route');

        $targetRoutes = ['user_home'];

        if (!in_array($route, $targetRoutes, true)) {
            return;
        }

        $token = $this->tokenStorage->getToken();
        if (null === $token) {
            return;
        }

        /** @var Customer|null $customer */
        $customer = $token->getUser();
        if (!$customer instanceof Customer) {
            return;
        }

        $status = $customer->getCustomerPaymentStatus();
        $now = new DateTimeImmutable();
        $daysLeft = null;

        if ($status === CustomerPaymentStatusEnum::NOT_PAID && $customer->isTrialActive()) {
            $trialEnd = $customer->getTrialEndDate();
            $daysLeft = $trialEnd->diff($now)->days;
            $this->twig->addGlobal('customerPaymentStatus', 'trial');
            $this->twig->addGlobal('customerPaymentDaysLeft', $daysLeft);
            return;
        }

        if ($status === CustomerPaymentStatusEnum::NOT_PAID) {
            $this->twig->addGlobal('customerPaymentStatus', 'not_paid');
            return;
        }

        if ($status === CustomerPaymentStatusEnum::PAID) {

            $latestTransaction = $customer->getTransactions()->first();

            if (!$latestTransaction || !$latestTransaction->getPaidUntil()) {
                $this->twig->addGlobal('customerPaymentStatus', 'not_paid');
                return;
            }

//            $plan = $latestTransaction->getPlan();
//            $amount = $latestTransaction->getAmount();
//
//            $pricePerMonth = $this->planPriceResolver->getPricePerMonth($plan);
//
//            if ($pricePerMonth === null) {
//                throw new \InvalidArgumentException("Unknown plan: $plan");
//            }
//
//            if ($amount <= 0) {
//                throw new \InvalidArgumentException("Invalid amount: $amount for plan: $plan");
//            }

//            $monthsPaid = (int)floor($amount / $pricePerMonth);
//            $daysPaid = $monthsPaid * 31;
//            $paidUntil = $latestTransaction->getCreatedAt()->modify("+{$daysPaid} days");

            $paidUntil = $latestTransaction->getPaidUntil();

            if ($paidUntil > $now) {
                $daysLeft = $paidUntil->diff($now)->days;
            }

            $this->twig->addGlobal('customerPaymentStatus', 'paid');
            $this->twig->addGlobal('customerPaymentDaysLeft', $daysLeft);
        }

    }
}
