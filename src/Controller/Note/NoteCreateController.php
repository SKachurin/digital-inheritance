<?php

namespace App\Controller\Note;


use App\CommandHandler\Note\Create\NoteCreateInputDto;
use App\Entity\Note;
use App\Form\Type\NoteCreationType;
use App\Form\Type\NoteCreationType1;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;


class NoteCreateController extends AbstractController
{
    private MessageBusInterface $commandBus;


    public function __construct(MessageBusInterface $commandBus)
    {
        $this->commandBus = $commandBus;
    }


    public function create(Request $request): Response
    {
        $customer = $this->getUser();
        $note = null;

        if ($customer instanceof \App\Entity\Customer) {

            $note = new NoteCreateInputDto(
                $customer,
                "Replace this text with your data. \nThere are no limits to what you can enter here (though the field limit is 5000 characters). This could include crypto wallet credentials, online bank credentials, etc. \nThis envelope will be encrypted with half of our security key and half of the hash from your answer to the security question. \nOnce encrypted, your answers will be deleted from our database, leaving only you and God with the knowledge.",

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

                $this->addFlash('success', 'Your Envelope is being processed.');

                $note = $handledStamp->getResult();

                /** @var Note $note */
                $noteId = $note->getId();

                $form1 = $this->createForm(NoteCreationType1::class, $note, ['customerId' => $customer->getId()]);

                return $this->render('noteCreate.html.twig', [
                    'form' => $form1,
                    'decodedNote' => true,
                    'noteId' => $noteId
                ]);

    //            return $this->redirectToRoute('customer_creating');
            }

            return $this->render('noteCreate.html.twig', [
                'form' => $form,
                'decodedNote' => false,
            ]);

        }
        return $this->render('dashboard.html.twig');
    }
}