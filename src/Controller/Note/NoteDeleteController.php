<?php

namespace App\Controller\Note;

use App\CommandHandler\Note\Delete\NoteDeleteInputDto;
use App\Entity\Customer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class NoteDeleteController extends AbstractController
{
    private MessageBusInterface $commandBus;


    public function __construct(MessageBusInterface $commandBus)
    {
        $this->commandBus = $commandBus;
    }

    /**
     * @param int     $noteId
     * @param Request $request
     *
     * @return Response
     */
    public function delete(int $noteId, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var Customer|null $customer */
        $customer = $this->getUser();

        if (!$customer instanceof Customer) {
            throw new AccessDeniedException('You must be logged in to delete a note.');
        }

        // CSRF Protection
        $submittedToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete_note', $submittedToken)) {
            throw new AccessDeniedException('Invalid CSRF token.');
        }

        $inputDto = new NoteDeleteInputDto($customer, $noteId);

        $this->commandBus->dispatch($inputDto);

        $this->addFlash('success', 'Your note has been successfully deleted.');

        return $this->redirectToRoute('user_home');
    }
}