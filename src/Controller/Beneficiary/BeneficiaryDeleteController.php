<?php

namespace App\Controller\Beneficiary;

use App\CommandHandler\Beneficiary\Delete\BeneficiaryDeleteInputDto;
use App\Entity\Customer;
use App\Repository\NoteRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
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
            throw new AccessDeniedException('You must be logged in to delete a note.');
        }

        $note = $this->noteRepository->findOneBy(['beneficiary' => $beneficiaryId, 'customer' => $customer]);

        if (!$note) {
            throw new AccessDeniedException('You do not have permission to delete this Heir.');
        }

        // CSRF Protection
        $submittedToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete_beneficiary', $submittedToken)) {
            throw new AccessDeniedException('Invalid CSRF token.');
        }

        $inputDto = new BeneficiaryDeleteInputDto($customer, $beneficiaryId);

        $this->commandBus->dispatch($inputDto);

        $this->addFlash('success', $this->translator->trans('errors.flash.heir_deleted'));

        return $this->redirectToRoute('user_home');
    }
}