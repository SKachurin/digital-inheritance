<?php

namespace App\Controller\Note;


use App\CommandHandler\Note\Create\NoteCreateInputDto;
use App\Entity\Note;
use App\Form\Type\NoteCreationType;
//use App\Form\Type\NoteCreationType1;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Contracts\Translation\TranslatorInterface;

class NoteCreateController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $commandBus,
        private TranslatorInterface $translator
    )
    {
    }

    public function create(Request $request): Response
    {
        $customer = $this->getUser();
        $note = null;
        $beneficiaries = $customer->getBeneficiary();

        if ($beneficiaries->isEmpty()) {
            $this->addFlash('info', $this->translator->trans('errors.flash.add_heir'));

            return $this->redirectToRoute('user_home');
        }

        if ($customer instanceof \App\Entity\Customer) {

            $defaultText = $this->translator->trans('note_creation.default_text');
            $note = new NoteCreateInputDto(
                $customer,
                $defaultText
            );

            $form = $this->createForm(NoteCreationType::class, $note, ['customerId' => $customer->getId(),'decodedNote' => false]);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {

                /** @var NoteCreateInputDto $customerNote */
                $customerNote = $form->getData();

                $envelope = $this->commandBus->dispatch($customerNote);

                $handledStamp = $envelope->last(HandledStamp::class);

                if (!$handledStamp) {
                    throw new UnprocessableEntityHttpException('500 internal error (CommandBus not responding).');
                }

                $this->addFlash('success', $this->translator->trans('errors.flash.envelop_is_processed'));

                return $this->redirectToRoute('user_home');

            }

            return $this->render('note/noteCreate.html.twig', [
                'form' => $form,
                'decodedNote' => false,
            ]);

        }
        return $this->render('dashboard.html.twig');
    }
}