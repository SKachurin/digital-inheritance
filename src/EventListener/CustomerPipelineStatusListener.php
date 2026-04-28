<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\Customer;
use App\Repository\PipelineRepository;
use DateTimeImmutable;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Twig\Environment;

class CustomerPipelineStatusListener
{
    private const COOKIE_NAME = '_pipeline';
    private const COOKIE_DURATION = '+5 minutes';

    private ?string $cookieToSet = null;
    private bool $clearCookie = false;

    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
        private readonly PipelineRepository $pipelineRepository,
        private readonly Environment $twig,
    ) {}

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $route = $request->attributes->get('_route');

        $targetRoutes = [
            'user_home_pay', 'user_home', 'user_home_1', 'user_home_ref',
            'user_home_email', 'user_home_email_', 'user_home_phone',
            'user_home_social', 'user_home_heir',
            'user_home_env', 'user_home_pipe',
            'pipeline_create','pipeline_edit', 'contact_create',
            'contact_edit', 'beneficiary_create',
            'beneficiary_edit', 'customer_delete', 'note_create',
            'note_edit', 'user_home_phone_'
        ];

        if (!in_array($route, $targetRoutes, true)) {
            return;
        }

        $token = $this->tokenStorage->getToken();
        $customer = $token?->getUser();
        if (!$customer instanceof Customer) {
            return;
        }

        // Always clear cookie on these routes
        $forceFreshPipelineCheck = in_array($route, [
            'user_home_pipe',
            'pipeline_create',
            'pipeline_edit',
            'user_home_pay'
        ], true);

        if ($forceFreshPipelineCheck) {
            $this->clearCookie = true;
        }

        $customerId = $customer->getId();
        $now = new DateTimeImmutable();

        $cookieValue = $request->cookies->get(self::COOKIE_NAME);

        if ($cookieValue !== null && !$this->clearCookie) {
            if (preg_match('/^(\d+):([a-z_]+):(.+):(\d+)$/', $cookieValue, $m)) {
                [$all, $pipelineIdFromCookie, $pipelineStatusFromCookie, $timestamp, $cookieCustomerId] = $m;

                $pipelineIdFromCookie = (int) $pipelineIdFromCookie;

                if ((int) $cookieCustomerId === $customerId) {
                    try {
                        $createdAt = new DateTimeImmutable($timestamp);

                        if ($createdAt->modify(self::COOKIE_DURATION) > $now) {
                            $this->twig->addGlobal('customerHasPipeline', $pipelineIdFromCookie);
                            $this->twig->addGlobal(
                                'customerPipelineStatus',
                                $pipelineStatusFromCookie !== 'none' ? $pipelineStatusFromCookie : null
                            );

                            return;
                        }
                    } catch (\Exception) {
                        // ignore
                    }
                }
            }
        }

        // fallback — fresh check
        $pipelineId = $this->pipelineRepository->customerHasPipeline($customer);
        $pipelineId = $pipelineId ?: 0;

        $customerPipelineStatus = null;

        if ($pipelineId > 0) {
            $customerPipelineStatus = $this->pipelineRepository->getPipelineStatusById($pipelineId);
        }

        $this->twig->addGlobal('customerHasPipeline', $pipelineId);
        $this->twig->addGlobal('customerPipelineStatus', $customerPipelineStatus);

        $this->cookieToSet = sprintf(
            '%d:%s:%s:%d',
            $pipelineId,
            $customerPipelineStatus ?? 'none',
            $now->format(DATE_ATOM),
            $customerId
        );
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
        }

        if ($this->cookieToSet !== null) {
            $cookie = Cookie::create(self::COOKIE_NAME)
                ->withValue($this->cookieToSet)
                ->withExpires($now->modify(self::COOKIE_DURATION))
                ->withSecure(true)
                ->withHttpOnly(true)
                ->withSameSite('lax');

            $response->headers->setCookie($cookie);
        }
    }
}
