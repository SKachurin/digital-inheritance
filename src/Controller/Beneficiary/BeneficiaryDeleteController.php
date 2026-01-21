<?php

namespace App\Controller\Beneficiary;

use App\CommandHandler\Beneficiary\Delete\BeneficiaryDeleteInputDto;
use App\Entity\Customer;
use App\Repository\NoteRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class BeneficiaryDeleteController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $commandBus,
        private NoteRepository      $noteRepository,
        private TranslatorInterface $translator
    )
    {
    }

    /**
     * @param int     $beneficiaryId
     * @param Request $request
     *
     * @return Response
     */
    public function delete(int $beneficiaryId, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var Customer|null $customer */
        $customer = $this->getUser();

        if (!$customer instanceof Customer) {
            $this->addFlash('warning', $this->translator->trans('errors.flash.login_required'));
            return $this->redirectToRoute('user_login');
        }

        $note = $this->noteRepository->findOneBy(['beneficiary' => $beneficiaryId, 'customer' => $customer]);

        if (!$note) {
            $this->addFlash('warning', $this->translator->trans('errors.flash.no_permission'));
            return $this->redirectToRoute('404');
        }

        // CSRF Protection
        $submittedToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete_beneficiary', $submittedToken)) {
            $this->addFlash('warning', $this->translator->trans('errors.flash.no_permission'));
            return $this->redirectToRoute('404');
        }

        $inputDto = new BeneficiaryDeleteInputDto($customer, $beneficiaryId);

        $this->commandBus->dispatch($inputDto);

        $this->addFlash('success', $this->translator->trans('errors.flash.heir_deleted'));

        return $this->redirectToRoute('user_home');
    }
}