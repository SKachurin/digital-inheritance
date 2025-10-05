<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\Customer;
use App\Entity\Transaction;
use App\Enum\CustomerPaymentStatusEnum;
use DateTimeImmutable;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Twig\Environment;
//use Psr\Log\LoggerInterface;

class CustomerPaymentStatusListener
{
    private const COOKIE_NAME = '_payment';
    private const COOKIE_DURATION = '+15 minutes';
    private ?string $cookieToSet = null;
    private bool $clearCookie = false;

    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
        private readonly Environment           $twig,
//        private readonly LoggerInterface       $logger,
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

        $targetRoutes = [
            'user_home', 'user_home_1', 'user_home_pay',
            'user_home_email', 'user_home_email_',
            'user_home_phone', 'user_home_phone_',
            'user_home_social', 'user_home_heir',
            'user_home_env', 'user_home_pipe',
            'pipeline_create', 'contact_create',
            'contact_edit', 'beneficiary_create',
            'beneficiary_edit', 'customer_delete',
            'user_home_ref'
        ];

        if (!in_array($route, $targetRoutes, true)) {
            return;
        }

        $token = $this->tokenStorage->getToken();
        if (null === $token) {
            return;
        }

        /** @var Customer|null $customer */
        $customer = $token?->getUser();
        if (!$customer instanceof Customer) {
            return;
        }

        // Always clear cookie on payment route
        if ($route === 'user_home_pay') {
            $this->clearCookie = true;
        }


        $customerId = $customer->getId();
        $now = new DateTimeImmutable();

        $cookieValue = $request->cookies->get(self::COOKIE_NAME);

        if ($cookieValue && !$this->clearCookie) {
            if (preg_match('/^(trial|paid|not_paid):(\d+):(.+):(\d+)$/', $cookieValue, $m)) {
                [$all, $status, $days, $timestamp, $cookieCustomerId] = $m;
                if ((int)$cookieCustomerId === $customerId) {
                    try {
                        $createdAt = new DateTimeImmutable($timestamp);
                        if ($createdAt->modify(self::COOKIE_DURATION) > $now) {
                            $this->twig->addGlobal('customerPaymentStatus', $status);
                            $this->twig->addGlobal('customerPaymentDaysLeft', (int)$days);
                            return;
                        }
                    } catch (\Exception) {
                        // ignore
                    }
                }
            }
        }

        // Fallback: calculate fresh
        $status = $customer->getCustomerPaymentStatus();

        if ($status === CustomerPaymentStatusEnum::NOT_PAID && $customer->isTrialActive()) {
            $trialEnd = $customer->getTrialEndDate();
            $daysLeft = $trialEnd->diff($now)->days;
            $this->twig->addGlobal('customerPaymentStatus', CustomerPaymentStatusEnum::TRIAL->value);
            $this->twig->addGlobal('customerPaymentDaysLeft', $daysLeft);

            $this->cookieToSet = sprintf('%s:%d:%s:%d', CustomerPaymentStatusEnum::TRIAL->value, $daysLeft, $now->format(DATE_ATOM), $customerId);
            return;
        }

        if ($status === CustomerPaymentStatusEnum::NOT_PAID) {
            $this->twig->addGlobal('customerPaymentStatus', CustomerPaymentStatusEnum::NOT_PAID->value);

            $this->cookieToSet = sprintf('%s:%d:%s:%d', CustomerPaymentStatusEnum::NOT_PAID->value, 0, $now->format(DATE_ATOM), $customerId);
            return;
        }

        if ($status === CustomerPaymentStatusEnum::PAID) {

            $latestTransaction = $customer->getTransactions()->first();

            if (!$latestTransaction instanceof Transaction|| !$latestTransaction->getPaidUntil()) {
                $this->twig->addGlobal('customerPaymentStatus', CustomerPaymentStatusEnum::NOT_PAID->value);

                $this->cookieToSet = sprintf('%s:%d:%s:%d', CustomerPaymentStatusEnum::NOT_PAID->value, 0, $now->format(DATE_ATOM), $customerId);
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

            $daysLeft = max(0, $paidUntil > $now ? $paidUntil->diff($now)->days : 0);

            $this->twig->addGlobal('customerPaymentStatus', CustomerPaymentStatusEnum::PAID->value);
            $this->twig->addGlobal('customerPaymentDaysLeft', $daysLeft);

            $this->cookieToSet = sprintf('%s:%d:%s:%d', CustomerPaymentStatusEnum::PAID->value, $daysLeft, $now->format(DATE_ATOM), $customerId);
        }

    }
    public function onKernelResponse(ResponseEvent $event): void
    {
        $response = $event->getResponse();
        $now = new DateTimeImmutable();


        if ($this->clearCookie) {
            $expiredCookie = Cookie::create(self::COOKIE_NAME)
                ->withValue('')
                ->withExpires($now->modify('-1 day'))
                ->withSecure(true)
                ->withHttpOnly(true)
                ->withSameSite('lax');
            $response->headers->setCookie($expiredCookie);

        } elseif ($this->cookieToSet !== null) {
            $cookie = Cookie::create(self::COOKIE_NAME)
                ->withValue($this->cookieToSet)
                ->withExpires($now->modify(self::COOKIE_DURATION))
                ->withSecure(true)
                ->withHttpOnly(true)
                ->withSameSite('lax');
            $response->headers->setCookie($cookie);
        }
    }


    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 100],
            KernelEvents::RESPONSE => ['onKernelResponse', -100],
        ];
    }
}
