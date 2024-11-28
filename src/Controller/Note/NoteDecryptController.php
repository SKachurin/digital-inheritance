<?php

namespace App\Controller\Note;

use App\CommandHandler\Note\Decrypt\NoteDecryptInputDto;
use App\Entity\Note;
use App\Form\Type\NoteDecryptType;
use App\Form\Type\NoteDecryptType1;
use App\Repository\NoteRepository;
use App\Service\CryptoService;
use Random\RandomException;
use SodiumException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Annotation\Route;

class NoteDecryptController extends AbstractController
{
    private MessageBusInterface $commandBus;
    private NoteRepository $repository;
    private CryptoService $cryptoService;
    public function __construct(
        NoteRepository $repository,
        MessageBusInterface $commandBus,
        CryptoService $cryptoService
    )
    {
        $this->repository = $repository;
        $this->commandBus = $commandBus;
        $this->cryptoService = $cryptoService;
    }

    /**
     * @Route("/note/{noteId}/decrypt/{question}", name="note_decrypt")
     * @throws SodiumException|RandomException
     */
    public function decrypt(int $noteId, string $question, Request $request): Response
    {
        $currentCustomer = $this->getUser();

        $note = $this->repository->getOneBy(['id' => $noteId]);
        if (!$note instanceof Note) {
            throw new \UnexpectedValueException('There is no note with id ' . $noteId);
        }

        $noteCustomer = $note->getCustomer();

        if ($noteCustomer !== $currentCustomer) {
            throw new \UnexpectedValueException('It is not your Envelope');

//            $this->addFlash('error', 'It is not your Envelope');
//            return $this->redirectToRoute('customer_creating');
        }

        $dto = new NoteDecryptInputDto($noteCustomer);

        if ($question === 'customerTextAnswerOne') {
            $dto
                ->setCustomerTextAnswerOne($note->getCustomerTextAnswerOne())
                ->setCustomerFirstQuestion(
                    $this->cryptoService->decryptData(
                        $noteCustomer->getCustomerFirstQuestion()
                    )
                )
            ;
        }
        if ($question === 'customerTextAnswerTwo') {
            $dto
                ->setCustomerTextAnswerTwo($note->getCustomerTextAnswerTwo())
                ->setCustomerSecondQuestion(
                    $this->cryptoService->decryptData(
                        $noteCustomer->getCustomerSecondQuestion()
                    )
                )
            ;
        }

        $form = $this->createForm(NoteDecryptType::class, $dto);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            /** @var NoteDecryptInputDto $noteData */
            $noteData = $form->getData();

            $envelope = $this->commandBus->dispatch($noteData);

            $handledStamp = $envelope->last(HandledStamp::class);

            if (!$handledStamp) {
                throw new UnprocessableEntityHttpException('500 internal error (CommandBus not responding).');
            }

            $this->addFlash('success', 'Your Envelope is being processed.');

            $handledResult = $handledStamp->getResult();

            $form1 = $this->createForm(NoteDecryptType1::class, $handledResult);

            return $this->render('note/noteDecrypt.html.twig', [
                'form' => $form1->createView(),
            ]);
        }

        return $this->render('note/noteDecrypt.html.twig', [
            'form' => $form
        ]);
    }
}