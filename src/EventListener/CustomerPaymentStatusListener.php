<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\Customer;
use App\Enum\CustomerPaymentStatusEnum;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Twig\Environment;

class CustomerPaymentStatusListener
{
    private TokenStorageInterface $tokenStorage;
    private Environment $twig;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        Environment $twig
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->twig = $twig;
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

        if ($customer->isTrialActive()) {
            $this->twig->addGlobal('customerPaymentStatus', 'trial');
            return;
        }

        if ($customer->getCustomerPaymentStatus() === CustomerPaymentStatusEnum::NOT_PAID) {
            $this->twig->addGlobal('customerPaymentStatus', 'not_paid');
            return;
        }

        $this->twig->addGlobal('customerPaymentStatus', 'paid');

    }
}
