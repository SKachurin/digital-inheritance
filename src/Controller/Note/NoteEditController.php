<?php

namespace App\Controller\Note;

use App\CommandHandler\Note\Edit\NoteEditCounterHandler;
use App\CommandHandler\Note\Edit\NoteEditInputDto;
use App\CommandHandler\Note\Edit\NoteEditOutputDto;
use App\CommandHandler\Note\Edit\NoteEditTextHandler;
use App\CommandHandler\Note\Edit\NoteEditTextInputDto;
use App\Entity\Note;
use App\Form\Type\NoteEditType;
use App\Form\Type\NoteEditType1;
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
use Doctrine\ORM\EntityManagerInterface;

class NoteEditController extends AbstractController
{
    private MessageBusInterface $commandBus;
    private NoteRepository $repository;
    private CryptoService $cryptoService;
    private NoteEditTextHandler $noteEditTextHandler;
    private NoteEditCounterHandler $noteEditCounterHandler;
    private EntityManagerInterface $entityManager;
    public function __construct(
        NoteRepository $repository,
        MessageBusInterface $commandBus,
        CryptoService $cryptoService,
        NoteEditTextHandler $noteEditTextHandler,
        NoteEditCounterHandler $noteEditCounterHandler,
        EntityManagerInterface $entityManager
    )
    {
        $this->repository = $repository;
        $this->commandBus = $commandBus;
        $this->cryptoService = $cryptoService;
        $this->noteEditTextHandler = $noteEditTextHandler;
        $this->noteEditCounterHandler = $noteEditCounterHandler;
        $this->entityManager = $entityManager;
    }

    /**
     * @throws SodiumException|RandomException
     * @throws \Exception
     */
    public function edit(int $noteId, Request $request): Response
    {
        $currentCustomer = $this->getUser();
        $decodedNote = false;

        $note = $this->repository->getOneBy(['id' => $noteId]);
        if (!$note instanceof Note) {
            throw new \UnexpectedValueException('There is no note with id ' . $noteId); //TODO Translate
        }

        $noteCustomer = $note->getCustomer();

        if ($noteCustomer !== $currentCustomer) {
            throw new \UnexpectedValueException('It is not your Envelope'); //TODO Translate
        }

        $dto = new NoteEditInputDto($noteCustomer);

        $dto
            ->setCustomerTextAnswerOne($note->getCustomerTextAnswerOne())
            ->setCustomerFirstQuestion(
                $this->cryptoService->decryptData(
                    $noteCustomer->getCustomerFirstQuestion()
                )
            )
        ;
        $dto
            ->setCustomerTextAnswerTwo($note->getCustomerTextAnswerTwo())
            ->setCustomerSecondQuestion(
                $this->cryptoService->decryptData(
                    $noteCustomer->getCustomerSecondQuestion()
                )
            )
        ;

        if ($note->getBeneficiary()) {
            $dto
                ->setBeneficiaryTextAnswerOne($note->getBeneficiaryTextAnswerOne())
                ->setBeneficiaryFirstQuestion(
                    $this->cryptoService->decryptData(
                        $note->getBeneficiary()->getBeneficiaryFirstQuestion()
                    )
                )
            ;
            $dto
                ->setBeneficiaryTextAnswerTwo($note->getBeneficiaryTextAnswerTwo())
                ->setBeneficiarySecondQuestion(
                    $this->cryptoService->decryptData(
                        $note->getBeneficiary()->getBeneficiarySecondQuestion()
                    )
                )
            ;
        }

        $dto->setAttemptCount($note->getAttemptCount());
        $dto->setLockoutUntil($note->getLockoutUntil());
        //TODO show Statuses for Note when first try

        $form = $this->createForm(NoteEditType::class, $dto, ['beneficiary' => $note->getBeneficiary()]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            /** @var NoteEditInputDto $noteData */
            $noteData = $form->getData();

            $envelope = $this->commandBus->dispatch($noteData);

            $handledStamp = $envelope->last(HandledStamp::class);

            if (!$handledStamp) {
                throw new UnprocessableEntityHttpException('500 internal error (CommandBus not responding).');
            }

            $handledResult = $handledStamp->getResult();

            // Dispatch to NoteEditCounterHandler to update attempts/lockouts:
            $this->noteEditCounterHandler->__invoke($handledResult);


            if ($handledResult->getAttemptCount() != 0) {

                $form1 = $this->createForm(NoteEditType::class, $dto, ['beneficiary' => $note->getBeneficiary()]);

            } else {

                $form1 = $this->createForm(NoteEditType1::class, $handledResult, ['beneficiary' => $note->getBeneficiary()]);
                $decodedNote = true;
            }

            $note->setAttemptCount($handledResult->getAttemptCount());
            $note->setLockoutUntil($handledResult->getLockoutUntil());

            $this->entityManager->persist($note);
            $this->entityManager->flush();

            return $this->render('note/noteEdit.html.twig', [
                'form' => $form1->createView(),
                'beneficiary' => $note->getBeneficiary(),
                'decodedNote' => $decodedNote,
                'customerCongrats' => $handledResult->getCustomerCongrats(),
            ]);
        }

        if ($request->request->has('note_edit_type1')) {

            $noteEditOutputDto = new NoteEditOutputDto($currentCustomer);

            $form1 = $this->createForm(NoteEditType1::class, $noteEditOutputDto, ['beneficiary' => $note->getBeneficiary()]);

            $form1->handleRequest($request);

            if ($form1->isSubmitted() && $form1->isValid()) {

                /** @var NoteEditOutputDto $data */
                $data = $form1->getData();

                $inputDto = new NoteEditTextInputDto($currentCustomer, $note);
                $inputDto->setCustomerText($data->getCustomerText());

                $inputDto->setCustomerFirstQuestion($data->getCustomerFirstQuestion());
                $inputDto->setCustomerFirstQuestionAnswer($data->getCustomerFirstQuestionAnswer());
                $inputDto->setCustomerSecondQuestion($data->getCustomerSecondQuestion());
                $inputDto->setCustomerSecondQuestionAnswer($data->getCustomerSecondQuestionAnswer());

                if ($data->getBeneficiaryFirstQuestion()){
                    $inputDto->setBeneficiaryFirstQuestion($data->getBeneficiaryFirstQuestion());
                    $inputDto->setBeneficiaryFirstQuestionAnswer($data->getBeneficiaryFirstQuestionAnswer());
                }
               if ($data->getBeneficiarySecondQuestion()){
                   $inputDto->setBeneficiarySecondQuestion($data->getBeneficiarySecondQuestion());
                   $inputDto->setBeneficiarySecondQuestionAnswer($data->getBeneficiarySecondQuestionAnswer());
               }

                $this->noteEditTextHandler->__invoke($inputDto); // handler

                $this->addFlash('success', 'Your note has been updated.'); //TODO Translate

                return $this->redirectToRoute('user_home');
            }

        }

        return $this->render('note/noteEdit.html.twig', [
            'form' => $form,
            'decodedNote' => $decodedNote
        ]);
    }
}