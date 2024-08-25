<?php
namespace App\Controller\Beneficiary;

use App\CommandHandler\Beneficiary\Create\BeneficiaryCreateInputDto;
use App\CommandHandler\Customer\Create\CustomerCreateInputDto;
use App\Entity\Beneficiary;
use App\Entity\Customer;
use App\Entity\Note;
use App\Enum\CustomerSocialAppEnum;
use App\Form\Type\BeneficiaryCreateType;
use App\Form\Type\RegistrationType;
use App\Repository\BeneficiaryRepository;
use App\Repository\NoteRepository;
use App\Service\CryptoService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Random\RandomException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

class BeneficiaryCreateController extends AbstractController
{
    private MessageBusInterface $commandBus;
    private NoteRepository $noteRepository;
    private LoggerInterface $logger;
    private ParameterBagInterface $params;
    private EntityManagerInterface $entityManager;


    public function __construct(
        MessageBusInterface $commandBus,
        NoteRepository $noteRepository,
        LoggerInterface $logger,
        ParameterBagInterface $params,
        EntityManagerInterface $entityManager
    )
    {
        $this->commandBus = $commandBus;
        $this->noteRepository = $noteRepository;
        $this->logger = $logger;
        $this->params = $params;
        $this->entityManager = $entityManager;
    }

    /**
     * @throws \Exception
     */
    public function create(Request $request): Response
    {
        $customer = $this->getUser();

        if (! $customer instanceof \App\Entity\Customer) {
            return $this->redirectToRoute('user_login');
        }

        $beneficiary = new BeneficiaryCreateInputDto();

        $noteId = $this->noteRepository->customerHasNote($customer);

        if (!$noteId) {
            $this->addFlash('info', 'Create Envelope First.');

            return $this->redirectToRoute('user_home');
        }

        $form = $this->createForm(BeneficiaryCreateType::class, $beneficiary);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            /** @var BeneficiaryCreateInputDto $beneficiaryData */
            $beneficiaryData = $form->getData();

            $envelope = $this->commandBus->dispatch($beneficiaryData);

            $handledStamp = $envelope->last(HandledStamp::class);

            if (!$handledStamp) {
                throw new UnprocessableEntityHttpException('500 internal error (CommandBus not responding).');
            }

            $beneficiary = $handledStamp->getResult();


            $note = $this->noteRepository->find($noteId);

            $this->encodeNoteForBeneficiary($beneficiary, $customer, $note);


            $this->addFlash('success', 'Your Heir is created.');
            return $this->redirectToRoute('user_home');
        }

       return $this->render('beneficiaryCreate.html.twig', [
            'form' => $form,
       ]);

    }

    /**
     * @throws \Exception
     */
    private function encodeNoteForBeneficiary(Beneficiary $beneficiary, Customer $customer, Note $note): void
    {
        $note->setBeneficiary($beneficiary);

        $decryptedText = $this->decryptCustomerText($customer, $note);
//            $this->logger->info('Plaintext before encryption for beneficiary:', ['plaintext' => $decryptedText]);

        if ($decryptedText === false) {
            throw new \RuntimeException('Decryption failed for CustomerTextAnswers.');
        }

        $this->encodeNoteWithBeneficiaryAnswer($note, $beneficiary, $decryptedText);

        // Clear the customer's question answers after use
        $customer->setCustomerFirstQuestionAnswer(' ');
        $customer->setCustomerSecondQuestionAnswer(' ');

        $this->entityManager->persist($customer);
        $this->entityManager->persist($beneficiary);
        $this->entityManager->persist($note);
        $this->entityManager->flush();
    }

    /**
     * @throws \SodiumException
     * @throws \Exception
     */
    private function decryptCustomerText(Customer $customer, Note $note): ?string
    {
        $decryptedText = null;

        // decrypt with the first question answer
        if ($customer->getCustomerFirstQuestionAnswer() !== null && $customer->getCustomerFirstQuestionAnswer() !== ' ') {
            $personalString = $this->decryptAnswer($customer->getCustomerFirstQuestionAnswer());

            $cryptoService = new CryptoService($this->params, $this->logger, $personalString);
            $decryptedText = $cryptoService->decryptData($note->getCustomerTextAnswerOne());

            if ($decryptedText !== false) {
                return $decryptedText;
            }
        }

        // decrypt with the second question answer
        if (!$decryptedText && $customer->getCustomerSecondQuestionAnswer() !== null && $customer->getCustomerSecondQuestionAnswer() !== ' ') {
            $personalString = $this->decryptAnswer($customer->getCustomerSecondQuestionAnswer());

            $cryptoService = new CryptoService($this->params, $this->logger, $personalString);
            $decryptedText = $cryptoService->decryptData($note->getCustomerTextAnswerTwo());

            if ($decryptedText !== false) {
                return $decryptedText;
            }
        }

        return false;
    }

    /**
     * @throws RandomException
     * @throws \SodiumException
     * @throws \Exception
     */
    private function encodeNoteWithBeneficiaryAnswer(Note $note, Beneficiary $beneficiary, string $decryptedText): void
    {

        // Encrypt and set the first beneficiary answer
        if (!$note->getBeneficiaryTextAnswerOne() && $beneficiary->getBeneficiaryFirstQuestionAnswer()) {
            $personalString = $this->decryptAnswer($beneficiary->getBeneficiaryFirstQuestionAnswer());
            $cryptoService = new CryptoService($this->params, $this->logger, $personalString);
            $note->setBeneficiaryTextAnswerOne($cryptoService->encryptData($decryptedText));
            $beneficiary->setBeneficiaryFirstQuestionAnswer(' ');
        }

        // Encrypt and set the second beneficiary answer if the first one is already set
        if (!$note->getBeneficiaryTextAnswerTwo() && $beneficiary->getBeneficiarySecondQuestionAnswer()) {
            $personalString = $this->decryptAnswer($beneficiary->getBeneficiarySecondQuestionAnswer());
            $cryptoService = new CryptoService($this->params, $this->logger, $personalString);
            $note->setBeneficiaryTextAnswerTwo($cryptoService->encryptData($decryptedText));
           $beneficiary->setBeneficiarySecondQuestionAnswer(' ');
        }
    }

    /**
     * @throws \SodiumException
     * @throws \Exception
     */
    private function decryptAnswer(?string $encryptedAnswer): ?string
    {
        if ($encryptedAnswer === null || $encryptedAnswer === ' ') {
            return null;
        }

        $cryptoService = new CryptoService($this->params, $this->logger);
        return $cryptoService->decryptData($encryptedAnswer);
    }

}