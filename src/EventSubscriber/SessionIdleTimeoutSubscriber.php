<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final class SessionIdleTimeoutSubscriber implements EventSubscriberInterface
{
    private const KEY = '_last_activity';
    private const IDLE_SECONDS = 900;

    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {}

    public static function getSubscribedEvents(): array
    {
        // run after security
        return [KernelEvents::REQUEST => ['onKernelRequest', -10]];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        $route = $request->attributes->get('_route');
        if (\in_array($route, ['user_login', 'app_logout'], true)) {
            return;
        }

        $targetRoutes = [
            'user_home', 'user_home_1', 'user_home_ref',
            'user_home_email', 'user_home_phone',
            'user_home_social', 'user_home_heir',
            'user_home_env', 'user_home_pipe',
            'pipeline_create', 'contact_create',
            'contact_edit', 'pipeline_edit', 'beneficiary_create',
            'beneficiary_edit', 'customer_delete',
            'user_home_ref', 'note_create',
            'note_edit'
        ];

        if (!\in_array($route, $targetRoutes, true)) {
            return;
        }

        $token = $this->tokenStorage->getToken();
        if (!$token || !\is_object($token->getUser())) {
            return;
        }

        $session = $request->getSession();
        if (!$session) {
            return;
        }

        $now  = time();
        $last = (int) $session->get(self::KEY, $now);

        if (($now - $last) > self::IDLE_SECONDS) {
            // make sure session data is flushed then invalidate
            $session->save();
            $session->invalidate();
            $this->tokenStorage->setToken(null);

            $event->setResponse(new RedirectResponse(
                $this->urlGenerator->generate('user_login')
            ));

            // prevent later listeners/controller from continuing
            $event->stopPropagation();
            return;
        }

        $session->set(self::KEY, $now);
    }
}