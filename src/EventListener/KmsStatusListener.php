<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\Customer;
use App\Repository\KmsRepository;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Twig\Environment;

final class KmsStatusListener
{
    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
        private readonly KmsRepository $kmsRepository,
        private readonly Environment $twig,
    ) {}

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $route = $request->attributes->get('_route');

        $targetRoutes = [
            'user_home', 'user_home_1', 'user_home_ref',
            'user_home_email', 'user_home_email_', 'user_home_phone',
            'user_home_social', 'user_home_heir',
            'user_home_env', 'user_home_pipe',
            'pipeline_create', 'pipeline_edit', 'contact_create',
            'contact_edit', 'beneficiary_create',
            'beneficiary_edit', 'customer_delete',
            'user_home_phone_', 'note_edit'
        ];

        if (!in_array($route, $targetRoutes, true)) {
            return;
        }

        $token = $this->tokenStorage->getToken();

        if ($token === null) {
            return;
        }

        $customer = $token?->getUser();
        if (!$customer instanceof Customer) {
            return;
        }

        $usedKms = $this->kmsRepository->findUsedByCustomer($customer);

        $items = [];
        foreach ($usedKms as $kms) {
            $items[] = [
                'alias_key'   => 'dashboard.kms_status.real_label',
                'alias_value' => $kms->getAlias(), // e.g. "kms1"
                'last_health' => $kms->getLastHealth(),
                'check_date'  => $kms->getCheckDate(),
                'placeholder' => false,
            ];
        }

        // Add placeholders until we have 3 rows
        $slot = count($items) + 1;
        while (count($items) < 3) {
            $items[] = [
                'alias_key'   => 'dashboard.kms_status.placeholder_label',
                'alias_value' => $slot, // 2, 3, ...
                'last_health' => null,
                'check_date'  => null,
                'placeholder' => true,
            ];
            $slot++;
        }

        $this->twig->addGlobal('kms_statuses', $items);
    }
}