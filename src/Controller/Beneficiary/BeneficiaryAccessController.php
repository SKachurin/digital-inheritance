<?php

namespace App\Controller\Beneficiary;

use App\CommandHandler\Note\Decrypt\BeneficiaryNoteDecryptCounterHandler;
use App\CommandHandler\Note\Decrypt\BeneficiaryNoteDecryptInputDto;
use App\Form\Type\BeneficiaryDecryptType;
use App\Form\Type\BeneficiaryDecryptType1;
use App\Repository\VerificationTokenRepository;
use App\Repository\NoteRepository;
use App\Service\CryptoService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Random\RandomException;
use SodiumException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

class BeneficiaryAccessController extends AbstractController
{
    public function __construct(
        private readonly VerificationTokenRepository          $tokenRepository,
        private readonly NoteRepository                       $noteRepository,
        private readonly EntityManagerInterface               $entityManager,
        private readonly CryptoService                        $cryptoService,
        private readonly MessageBusInterface                  $commandBus,
        private readonly BeneficiaryNoteDecryptCounterHandler $beneficiaryNoteDecryptCounterHandler,
    ) {}

    /**
     * @throws SodiumException|RandomException
     * @throws \Exception
     */
    public function __invoke(string $token, Request $request): Response
    {
        $verificationToken = $this->tokenRepository->findOneBy(['token' => $token]);

        if (!$verificationToken || $verificationToken->getExpiresAt() < new \DateTimeImmutable()) {
            return $this->render('beneficiary/access_denied.html.twig');
        }

        $decodedNote = false;
        $contact = $verificationToken->getContact();
//        $contact = $this->contactRepository->getOneBy(['id' => 3]); // for test

        $beneficiary = $contact->getBeneficiary();
        if (!$beneficiary) {
            return $this->render('beneficiary/access_denied.html.twig');
        }

        $note = $this->noteRepository->findOneBy(['beneficiary' => $beneficiary]);
        if (!$note) {
            return $this->render('beneficiary/access_denied.html.twig');
        }

        $customerFullName = $note->getCustomer()->getCustomerFullName()
            ? $this->cryptoService->decryptData($note->getCustomer()->getCustomerFullName())
            : '_unknown_';

        $beneficiaryFullName = $beneficiary->getBeneficiaryFullName()
            ? $this->cryptoService->decryptData($beneficiary->getBeneficiaryFullName())
            : '_unknown_';

        $dto = new BeneficiaryNoteDecryptInputDto($note);
        $dto
            ->setBeneficiaryTextAnswerOne($note->getBeneficiaryTextAnswerOne())
            ->setBeneficiaryFirstQuestion(
                $this->cryptoService->decryptData(
                    $note->getBeneficiary()->getBeneficiaryFirstQuestion()
                )
            );
        $dto
            ->setBeneficiaryTextAnswerTwo($note->getBeneficiaryTextAnswerTwo())
            ->setBeneficiarySecondQuestion(
                $this->cryptoService->decryptData(
                    $note->getBeneficiary()->getBeneficiarySecondQuestion()
                )
            );

        $dto->setAttemptCount($note->getAttemptCount());
        $dto->setLockoutUntil($note->getLockoutUntil());

        $form = $this->createForm(BeneficiaryDecryptType::class, $dto);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            /** @var BeneficiaryNoteDecryptInputDto $noteData */
            $noteData = $form->getData();

            $envelope = $this->commandBus->dispatch($noteData);

            $handledStamp = $envelope->last(HandledStamp::class);

            if (!$handledStamp) {
                throw new UnprocessableEntityHttpException('500 internal error (CommandBus not responding).');
            }

            $handledResult = $handledStamp->getResult();

            // Dispatch to BeneficiaryNoteDecryptCounterHandler to update attempts/lockouts:
            $this->beneficiaryNoteDecryptCounterHandler->__invoke($handledResult);

            if ($handledResult->getAttemptCount() != 0) {

                $form1 = $this->createForm(BeneficiaryDecryptType::class, $dto);

            } else {

                $form1 = $this->createForm(BeneficiaryDecryptType1::class, $handledResult);
                $decodedNote = true;
            }

            $note->setAttemptCount($handledResult->getAttemptCount());
            $note->setLockoutUntil($handledResult->getLockoutUntil());

            $this->entityManager->persist($note);
            $this->entityManager->flush();

            return $this->render('beneficiary/access_granted.html.twig', [
                'form' => $form1->createView(),
                'beneficiary' => $note->getBeneficiary(),
                'decodedNote' => $decodedNote,
                'customerCongrats' => $handledResult->getBeneficiaryCongrats(),
                'customerFullName' => $customerFullName,
                'beneficiaryFullName' => $beneficiaryFullName,
            ]);

        }

        return $this->render('beneficiary/access_granted.html.twig', [
            'form' => $form,
            'decodedNote' => $decodedNote,
            'customerFullName' => $customerFullName,
            'beneficiaryFullName' => $beneficiaryFullName,
        ]);
    }
}
