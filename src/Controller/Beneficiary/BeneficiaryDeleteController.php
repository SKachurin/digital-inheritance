<?php

namespace App\Controller\Beneficiary;

use App\CommandHandler\Beneficiary\Delete\BeneficiaryDeleteInputDto;
use App\Entity\Customer;
use App\Repository\BeneficiaryRepository;
use App\Repository\NoteRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class BeneficiaryDeleteController extends AbstractController
{
    public function __construct(
        private MessageBusInterface    $commandBus,
        private NoteRepository         $noteRepository,
        private TranslatorInterface    $translator,
        private BeneficiaryRepository  $beneficiaryRepository,
    ) {}

    public function delete(int $beneficiaryId, Request $request): Response
    {

        $currentCustomer = $this->getUser();

        if (!$currentCustomer instanceof Customer) {
            $this->addFlash('warning', $this->translator->trans('errors.flash.login_required'));
            return $this->redirectToRoute('user_login');
        }

        // CSRF Protection (better to include the id in the token)
        $submittedToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete_beneficiary_'.$beneficiaryId, $submittedToken)) {
            $this->addFlash('warning', $this->translator->trans('errors.flash.no_permission'));
            return $this->redirectToRoute('404');
        }

        // REAL permission check: beneficiary must belong to this customer
        $beneficiary = $this->beneficiaryRepository->findOneBy([
            'id' => $beneficiaryId,
            'customer' => $currentCustomer,
        ]);

        if (!$beneficiary) {
            $this->addFlash('warning', $this->translator->trans('errors.flash.no_permission'));
            return $this->redirectToRoute('404');
        }

        // Block deletion if envelopes exist
        $notesCount = $this->noteRepository->count([
            'beneficiary' => $beneficiary,
            'customer' => $currentCustomer,
        ]);

        if ($notesCount > 0) {
            $this->addFlash('warning', $this->translator->trans('errors.flash.heir_has_envelopes'));
            return $this->redirectToRoute('user_home_env'); // or heirs list page
        }

        $inputDto = new BeneficiaryDeleteInputDto($currentCustomer, $beneficiaryId);
        $this->commandBus->dispatch($inputDto);

        $this->addFlash('success', $this->translator->trans('errors.flash.heir_deleted'));
        return $this->redirectToRoute('user_home');
    }
}