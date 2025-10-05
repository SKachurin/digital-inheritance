<?php

namespace App\EventListener;

use App\Entity\Customer;
use App\Repository\BeneficiaryRepository;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Twig\Environment;

class CustomerHasBeneficiaryListener
{
    private TokenStorageInterface $tokenStorage;
    private Environment $twig;
    private BeneficiaryRepository $beneficiaryRepository;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        Environment $twig,
        BeneficiaryRepository $beneficiaryRepository
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->twig = $twig;
        $this->beneficiaryRepository = $beneficiaryRepository;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $route = $request->attributes->get('_route');

        // Define the routes where this listener should be active
        $targetRoutes = ['user_home', 'user_home_1', 'user_home_heir',];

        if (in_array($route, $targetRoutes, true)) {
            $token = $this->tokenStorage->getToken();
            if (null === $token) {
                return;
            }

            /** @var Customer $customer */
            $customer = $token->getUser();
            if (!$customer instanceof Customer) {
                return;
            }

            // Check if the customer's note has a beneficiary
            $beneficiary = $this->beneficiaryRepository->findOneBy(['customer' => $customer]);

            if ($beneficiary !== null) {
                $beneficiaryId = $beneficiary->getId();

                // Add the beneficiary ID to Twig globals
                $this->twig->addGlobal('customerHasBeneficiary', $beneficiaryId);
            } else {
                $this->twig->addGlobal('customerHasBeneficiary', null);
            }
        }
    }
}
