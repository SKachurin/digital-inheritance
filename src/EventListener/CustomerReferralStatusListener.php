<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\Customer;
use App\Repository\CustomerRepository;
use DateTimeImmutable;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Twig\Environment;

class CustomerReferralStatusListener
{
    private const COOKIE_NAME = '_referrals';
    private const COOKIE_DURATION = '+15 minutes';

    private ?string $cookieToSet = null;
    private bool $clearCookie = false;

    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
        private readonly CustomerRepository $customerRepository,
        private readonly Environment $twig,
    ) {}

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $route = $request->attributes->get('_route');

        $targetRoutes = [
            'user_home', 'user_home_1', 'user_home_ref',
            'user_home_email', 'user_home_phone',
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
        $customer = $token?->getUser();
        if (!$customer instanceof Customer) {
            return;
        }

        $customerId = $customer->getId();
        $now = new DateTimeImmutable();

        // Always clear on referral route
        if ($route === 'user_home_ref') {
            $this->clearCookie = true;
        }

        $cookieValue = $request->cookies->get(self::COOKIE_NAME);

        if ($cookieValue && !$this->clearCookie) {
            if (preg_match('/^(\d+):(.+):(\d+)$/', $cookieValue, $m)) {
                [$all, $count, $timestamp, $cookieCustomerId] = $m;
                if ((int)$cookieCustomerId === $customerId) {
                    try {
                        $createdAt = new DateTimeImmutable($timestamp);
                        if ($createdAt->modify(self::COOKIE_DURATION) > $now) {
                            $this->twig->addGlobal('customerReferralCount', (int)$count);
                            return;
                        }
                    } catch (\Exception) {
                        // ignore
                    }
                }
            }
        }

        // fallback: calculate fresh
        $count = $this->customerRepository->countReferrals($customer);

        if ($route === 'user_home_ref') {
            $countActive = $this->customerRepository->countActiveReferrals($customer);
            $this->twig->addGlobal('customerActiveReferralsCount', $countActive);

            $referralCode = $this->customerRepository->getReferralCode($customer);
            $yourReferralLink = sprintf(
                'https://%s/?ref=%s',
                $request->getHost(),   // or your fixed domain like 'thedigitalheir.com'
                $referralCode
            );
            $this->twig->addGlobal('YourReferralLink', $yourReferralLink);
        }

        $this->twig->addGlobal('customerReferralCount', $count);

        $this->cookieToSet = sprintf('%d:%s:%d', $count, $now->format(DATE_ATOM), $customerId);
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $response = $event->getResponse();
        $now = new DateTimeImmutable();

        if ($this->clearCookie) {
            $expired = Cookie::create(self::COOKIE_NAME)
                ->withValue('')
                ->withExpires($now->modify('-1 day'))
                ->withSecure(true)
                ->withHttpOnly(true)
                ->withSameSite('lax');

            $response->headers->setCookie($expired);
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
