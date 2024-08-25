<?php

namespace App\EventListener;

use App\Entity\Customer;
use App\Repository\NoteRepository;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Twig\Environment;

class CustomerNoteHasBeneficiaryListener
{
    private TokenStorageInterface $tokenStorage;
    private Environment $twig;
    private NoteRepository $noteRepository;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        Environment $twig,
        NoteRepository $noteRepository
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->twig = $twig;
        $this->noteRepository = $noteRepository;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $route = $request->attributes->get('_route');

        // Define the routes where this listener should be active
        $targetRoutes = ['user_home'];

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
            $note = $this->noteRepository->customerHasNoteWithBeneficiary($customer);
            $hasBeneficiary = $note !== null;

            // Add the result to Twig globals
            $this->twig->addGlobal('customerNoteHasBeneficiary', $hasBeneficiary);
        }
    }
}
