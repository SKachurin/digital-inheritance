<?php

namespace App\EventListener;

use App\Entity\Customer;
use App\Repository\NoteRepository;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Twig\Environment;

class CustomerHasNoteListener
{
    private TokenStorageInterface $tokenStorage;
    private Environment $twig;
    private NoteRepository $noteRepository;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        Environment $twig,
        NoteRepository $noteRepository
    )
    {
        $this->tokenStorage = $tokenStorage;
        $this->twig = $twig;
        $this->noteRepository = $noteRepository;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $route = $request->attributes->get('_route');

        $targetRoutes = [
            'user_home',
            'user_home_1',
            'beneficiary_edit',
            'user_home_env'
        ];

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

            $hasNote = $this->noteRepository->customerHasNote($customer);
            $this->twig->addGlobal('customerHasNote', $hasNote);
        }
    }
}