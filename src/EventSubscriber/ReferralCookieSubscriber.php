<?php

namespace App\EventSubscriber;

use App\Entity\Customer;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Uid\Uuid;

class ReferralCookieSubscriber implements EventSubscriberInterface
{
    private const COOKIE_NAME = 'ref';
    private const COOKIE_DURATION = '+1 year';
    private ?string $referralToStore = null;

    public function __construct(
        private TokenStorageInterface $tokenStorage
    ) {}

    public function onKernelRequest(RequestEvent $event): void
    {
        // Skip if user is logged in
        $token = $this->tokenStorage->getToken();
        $user = $token?->getUser();
        if ($user instanceof Customer) {
            return;
        }

        $request = $event->getRequest();
        $refParam = $request->query->get(self::COOKIE_NAME);
        $refCookie = $request->cookies->get(self::COOKIE_NAME);

        if ($refParam && Uuid::isValid($refParam)) {
            if (!$refCookie) {
                $this->referralToStore = $refParam;
            }
        } elseif ($refCookie) {
            // Refresh cookie TTL
            $this->referralToStore = $refCookie;
        }
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$this->referralToStore) {
            return;
        }

        $cookie = Cookie::create(self::COOKIE_NAME)
            ->withValue($this->referralToStore)
            ->withExpires(new \DateTime(self::COOKIE_DURATION))
            ->withSecure(true)
            ->withHttpOnly(false)
            ->withSameSite('lax');

        $event->getResponse()->headers->setCookie($cookie);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 100],
            KernelEvents::RESPONSE => ['onKernelResponse', -100],
        ];
    }
}
