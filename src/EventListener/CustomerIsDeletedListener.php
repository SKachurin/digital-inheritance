<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\Customer;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Twig\Environment;

class CustomerIsDeletedListener
{
    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
        private readonly Environment $twig,
    ) {}

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $route = $request->attributes->get('_route');

        // Only trigger on dashboard
        if ($route !== 'user_home' && $route !== 'user_home_1') {
            return;
        }

        $token = $this->tokenStorage->getToken();
        if (null === $token) {
            return;
        }

        $user = $token->getUser();
        if (!$user instanceof Customer) {
            return;
        }

        $isDeleted = $user->getDeletedAt() !== null;

        // Inject into Twig
        $this->twig->addGlobal('customerIsDeleted', $isDeleted);
    }
}
