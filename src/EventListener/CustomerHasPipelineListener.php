<?php

namespace App\EventListener;

use App\Entity\Customer;
use App\Repository\PipelineRepository;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Twig\Environment;

class CustomerHasPipelineListener
{
    private TokenStorageInterface $tokenStorage;
    private Environment $twig;
    private PipelineRepository $pipelineRepository;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        Environment $twig,
        PipelineRepository $pipelineRepository
    )
    {
        $this->tokenStorage = $tokenStorage;
        $this->twig = $twig;
        $this->pipelineRepository = $pipelineRepository;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $route = $request->attributes->get('_route');

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

            $hasPipeline = $this->pipelineRepository->customerHasPipeline($customer);
            $this->twig->addGlobal('customerHasPipeline', $hasPipeline);
        }
    }
}