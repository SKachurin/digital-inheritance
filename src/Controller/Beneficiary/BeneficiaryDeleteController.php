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

class BeneficiaryDeleteController extends AbstractController
{
    private MessageBusInterface $commandBus;
    private NoteRepository $noteRepository;


    public function __construct(MessageBusInterface $commandBus, NoteRepository $noteRepository)
    {
        $this->commandBus = $commandBus;
        $this->noteRepository = $noteRepository;
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

        $this->addFlash('success', 'Your Heir has been successfully deleted.');

        return $this->redirectToRoute('user_home');
    }
}