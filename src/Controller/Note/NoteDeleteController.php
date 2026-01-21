<?php

namespace App\Controller\Note;

use App\CommandHandler\Note\Delete\NoteDeleteInputDto;
use App\Entity\Customer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class NoteDeleteController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $commandBus,
        private TranslatorInterface $translator
    )
    {
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
            $this->addFlash('warning', $this->translator->trans('errors.flash.login_required'));
            return $this->redirectToRoute('user_login');
        }

        // CSRF Protection
        $submittedToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete_note', $submittedToken)) {
            $this->addFlash('warning', $this->translator->trans('errors.flash.no_permission'));
            return $this->redirectToRoute('404');
        }

        $inputDto = new NoteDeleteInputDto($customer, $noteId);

        $this->commandBus->dispatch($inputDto);

        $this->addFlash('success', $this->translator->trans('errors.flash.envelope_deleted'));

        return $this->redirectToRoute('user_home');
    }
}