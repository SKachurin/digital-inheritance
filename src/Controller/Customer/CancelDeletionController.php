<?php

declare(strict_types=1);

namespace App\Controller\Customer;

use App\Entity\Customer;
use App\Service\CustomerDeletionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Contracts\Translation\TranslatorInterface;

class CancelDeletionController extends AbstractController
{
    public function __construct(
        private readonly CustomerDeletionService $deletionService,
        private readonly TranslatorInterface $translator,
    ) {}

    public function __invoke(): RedirectResponse
    {
        /** @var Customer $customer */
        $customer = $this->getUser();

        $this->deletionService->cancelDeletion($customer);

        $this->addFlash('success', $this->translator->trans(
            'errors.flash.cancel_delete_success',
            [],
            'messages',
            $customer->getLang() ?? 'en'
        ));

        return $this->redirectToRoute('user_home');
    }
}
