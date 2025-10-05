<?php

namespace App\EventListener;

use App\Entity\Customer;
use App\Repository\PipelineRepository;
use DateTimeImmutable;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Twig\Environment;

class CustomerHasPipelineListener
{
    private const COOKIE_NAME = '_pipeline';
    private const COOKIE_DURATION = '+15 minutes';
    private ?string $cookieToSet = null;

    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
        private readonly Environment $twig,
        private readonly PipelineRepository $pipelineRepository
    ) {}

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $route = $request->attributes->get('_route');

        $targetRoutes = [
            'user_home_pipe', 'user_home_pay',
            'user_home', 'user_home_1', 'user_home_ref',
            'user_home_email', 'user_home_phone',
            'user_home_social', 'user_home_heir',
            'user_home_env', 'user_home_pipe',
            'pipeline_create', 'contact_create',
            'contact_edit', 'beneficiary_create',
            'beneficiary_edit', 'customer_delete',
        ];

        if (!in_array($route, $targetRoutes, true)) {
            return;
        }

        $token = $this->tokenStorage->getToken();
        if (null === $token) {
            return;
        }

        $customer = $token->getUser();
        if (!$customer instanceof Customer) {
            return;
        }

        // Always clear cookie on payment route
        if ($route === 'user_home_pay' || $route === 'user_home_pipe') {
            $this->clearCookie = true;
        }

        $customerId = $customer->getId();
        $now = new DateTimeImmutable();

        // First: try cookie cache
        $cookieValue = $request->cookies->get(self::COOKIE_NAME);

        if ($cookieValue) {
            if (preg_match('/^([01]):(.+):(\d+)$/', $cookieValue, $m)) {
                [$all, $hasPipeline, $timestamp, $cookieCustomerId] = $m;

                if ((int) $cookieCustomerId === $customerId) {
                    try {
                        $createdAt = new DateTimeImmutable($timestamp);
                        if ($createdAt->modify(self::COOKIE_DURATION) > $now) {
                            $this->twig->addGlobal('customerHasPipeline', (bool) $hasPipeline);
                            return;
                        }
                    } catch (\Exception) {
                        // ignore corrupted cookie
                    }
                }
            }
        }

        // Fallback: calculate fresh
        $hasPipeline = $this->pipelineRepository->customerHasPipeline($customer) !== null;
        $this->twig->addGlobal('customerHasPipeline', $hasPipeline);

        // Save cookie for later requests
        $this->cookieToSet = sprintf(
            '%d:%s:%d',
            $hasPipeline ? 1 : 0,
            $now->format(DATE_ATOM),
            $customerId
        );
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if ($this->cookieToSet === null) {
            return;
        }

        $response = $event->getResponse();
        $now = new DateTimeImmutable();

        $cookie = Cookie::create(self::COOKIE_NAME)
            ->withValue($this->cookieToSet)
            ->withExpires($now->modify(self::COOKIE_DURATION))
            ->withSecure(true)
            ->withHttpOnly(true)
            ->withSameSite('lax');

        $response->headers->setCookie($cookie);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 100],
            KernelEvents::RESPONSE => ['onKernelResponse', -100],
        ];
    }
}
