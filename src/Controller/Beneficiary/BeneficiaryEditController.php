<?php
namespace App\Controller\Beneficiary;

use App\CommandHandler\Beneficiary\Edit\BeneficiaryEditInputDto;
use App\CommandHandler\Beneficiary\Edit\BeneficiaryEditOutputDto;
use App\Entity\Beneficiary;
use App\Entity\Contact;
use App\Entity\Customer;
use App\Entity\Note;
use App\Form\Type\BeneficiaryEditType;
use App\Repository\BeneficiaryRepository;
use App\Repository\ContactRepository;
use App\Repository\NoteRepository;
use App\Service\CryptoService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Random\RandomException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class BeneficiaryEditController extends AbstractController
{
    private MessageBusInterface $commandBus;
    private NoteRepository $noteRepository;
    private BeneficiaryRepository $beneficiaryRepository;
    private ContactRepository $contactRepository;
    private LoggerInterface $logger;
    private ParameterBagInterface $params;
    private EntityManagerInterface $entityManager;

    public function __construct(
        MessageBusInterface $commandBus,
        NoteRepository $noteRepository,
        BeneficiaryRepository $beneficiaryRepository,
        ContactRepository $contactRepository,
        LoggerInterface $logger,
        ParameterBagInterface $params,
        EntityManagerInterface $entityManager
    ) {
        $this->commandBus = $commandBus;
        $this->noteRepository = $noteRepository;
        $this->beneficiaryRepository = $beneficiaryRepository;
        $this->contactRepository = $contactRepository;
        $this->logger = $logger;
        $this->params = $params;
        $this->entityManager = $entityManager;
    }

    /**
     * @throws RandomException
     * @throws \SodiumException
     * @throws \Exception
     */
    public function edit(Request $request, int $beneficiaryId): Response
    {
        $customer = $this->getUser();

        if (!$customer instanceof Customer) {
            return $this->redirectToRoute('user_login');
        }

        // Fetch the beneficiary
        $beneficiary = $this->beneficiaryRepository->find($beneficiaryId);

        if (!$beneficiary) {
            throw $this->createNotFoundException('Heir not found.'); //TODO Transl
        }

        // Verify that the beneficiary belongs to the customer
        $note = $this->noteRepository->findOneBy(['beneficiary' => $beneficiary, 'customer' => $customer]);

        if (!$note) {
            throw new AccessDeniedException('You do not have permission to edit this Heir.');
        }

        // Prepare the DTO with decrypted data
        $beneficiaryData = new BeneficiaryEditOutputDto();
        $cryptoService = new CryptoService($this->params, $this->logger);
        $beneficiaryEmails = $this->contactRepository->findBy([
            'beneficiary' => $beneficiary,
            'contactTypeEnum' => 'email'
        ]);
        $beneficiaryPhones = $this->contactRepository->findBy([
            'beneficiary' => $beneficiary,
            'contactTypeEnum' => 'phone'
        ]);

        $beneficiaryData->setBeneficiaryName($beneficiary->getBeneficiaryName());

        $beneficiaryData->setBeneficiaryFullName(
            $cryptoService->decryptData($beneficiary->getBeneficiaryFullName())
        );

        if ($beneficiaryEmails[0]) {
            $beneficiaryData->setBeneficiaryEmail(
                $cryptoService->decryptData(
                    $beneficiaryEmails[0]->getValue()
                )
            );
        }
        if ($beneficiaryEmails[1]) {
            $beneficiaryData->setBeneficiarySecondEmail(
                $cryptoService->decryptData(
                    $beneficiaryEmails[1]->getValue()
                )
            );
        }

        if ($beneficiaryPhones[0]) {
            $beneficiaryData->setBeneficiaryCountryCode(
                $beneficiaryPhones[0]->getCountryCode()
            );
            $beneficiaryData->setBeneficiaryFirstPhone(
                $cryptoService->decryptData(
                    $beneficiaryPhones[0]->getValue()
                )
            );
        }

        if ($beneficiaryPhones[1]) {
            $beneficiaryData->setBeneficiarySecondPhone(
                $cryptoService->decryptData(
                    $beneficiaryPhones[1]->getValue()
                )
            );
        }

        // Create and handle the form
        $form = $this->createForm(BeneficiaryEditType::class, $beneficiaryData);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var BeneficiaryEditInputDto $updatedData */
            $updatedData = $form->getData();

            // Update beneficiary fields
            $beneficiary->setBeneficiaryName($updatedData->getBeneficiaryName());
            $beneficiary->setBeneficiaryFullName(
                $cryptoService->encryptData($updatedData->getBeneficiaryFullName())
            );

            // Update contacts as needed
            $this->updateContacts($beneficiary, $updatedData, $cryptoService);

            $this->entityManager->flush();

            $this->addFlash('success', 'Your Heir has been updated.');
            return $this->redirectToRoute('user_home');
        }

        return $this->render('beneficiaryEdit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

//    /**
//     * @throws \SodiumException
//     * @throws \Exception
//     */
//    private function decryptCustomerText(Customer $customer, Note $note): ?string
//    {
//        $decryptedText = null;
//
//        /**
//         * @throws RandomException
//         * @throws \SodiumException
//         * @throws \Exception
//         */
//        // Decrypt with the first question answer
//        if ($customer->getCustomerFirstQuestionAnswer() !== null && $customer->getCustomerFirstQuestionAnswer() !== ' ') {
//            $personalString = $this->decryptAnswer($customer->getCustomerFirstQuestionAnswer());
//
//            $cryptoService = new CryptoService($this->params, $this->logger, $personalString);
//            $decryptedText = $cryptoService->decryptData($note->getCustomerTextAnswerOne());
//
//            if ($decryptedText !== false) {
//                return $decryptedText;
//            }
//        }
//
//        /**
//         * @throws RandomException
//         * @throws \SodiumException
//         * @throws \Exception
//         */
//        // Decrypt with the second question answer
//        if (!$decryptedText && $customer->getCustomerSecondQuestionAnswer() !== null && $customer->getCustomerSecondQuestionAnswer() !== ' ') {
//            $personalString = $this->decryptAnswer($customer->getCustomerSecondQuestionAnswer());
//
//            $cryptoService = new CryptoService($this->params, $this->logger, $personalString);
//            $decryptedText = $cryptoService->decryptData($note->getCustomerTextAnswerTwo());
//
//            if ($decryptedText !== false) {
//                return $decryptedText;
//            }
//        }
//
//        return false;
//    }
//
//    /**
//     * @throws RandomException
//     * @throws \SodiumException
//     * @throws \Exception
//     */
//    private function encodeNoteWithBeneficiaryAnswer(
//        Note $note,
//        Beneficiary $beneficiary,
//        string $decryptedText,
//        BeneficiaryEditInputDto $updatedData
//    ): void
//    {
//        // Encrypt and set the first beneficiary answer
//        if ($updatedData->getBeneficiaryFirstQuestionAnswer()) {
//            $personalString = $this->decryptAnswer($updatedData->getBeneficiaryFirstQuestionAnswer());
//            $cryptoService = new CryptoService($this->params, $this->logger, $personalString);
//            $note->setBeneficiaryTextAnswerOne($cryptoService->encryptData($decryptedText));
//            $beneficiary->setBeneficiaryFirstQuestionAnswer(' ');
//        }
//
//        // Encrypt and set the second beneficiary answer
//        if ($updatedData->getBeneficiarySecondQuestionAnswer()) {
//            $personalString = $this->decryptAnswer($updatedData->getBeneficiarySecondQuestionAnswer());
//            $cryptoService = new CryptoService($this->params, $this->logger, $personalString);
//            $note->setBeneficiaryTextAnswerTwo($cryptoService->encryptData($decryptedText));
//            $beneficiary->setBeneficiarySecondQuestionAnswer(' ');
//        }
//    }

//    /**
//     * @throws \SodiumException
//     * @throws \Exception
//     */
//    private function decryptAnswer(?string $encryptedAnswer): ?string
//    {
//        if ($encryptedAnswer === null || $encryptedAnswer === ' ') {
//            return null;
//        }
//
//        $cryptoService = new CryptoService($this->params, $this->logger);
//        return $cryptoService->decryptData($encryptedAnswer);
//    }

    /**
     * @throws RandomException
     * @throws \SodiumException
     */
    private function updateContacts(Beneficiary $beneficiary, BeneficiaryEditInputDto $updatedData, CryptoService $cryptoService): void
    {
        // Remove existing contacts
        foreach ($beneficiary->getContacts() as $contact) {
            $this->entityManager->remove($contact);
        }

        // Add updated contacts
        if ($updatedData->getBeneficiaryEmail()) {
            $this->persistContact($beneficiary, 'email', $cryptoService->encryptData($updatedData->getBeneficiaryEmail()));
        }

        if ($updatedData->getBeneficiarySecondEmail()) {
            $this->persistContact($beneficiary, 'email', $cryptoService->encryptData($updatedData->getBeneficiarySecondEmail()));
        }

        if ($updatedData->getBeneficiaryFirstPhone()) {
            $this->persistContact($beneficiary, 'phone', $cryptoService->encryptData($updatedData->getBeneficiaryFirstPhone()), $updatedData->getBeneficiaryCountryCode());
        }

        if ($updatedData->getBeneficiarySecondPhone()) {
            $this->persistContact($beneficiary, 'phone', $cryptoService->encryptData($updatedData->getBeneficiarySecondPhone()), $updatedData->getBeneficiaryCountryCode());
        }
    }

    private function persistContact(Beneficiary $beneficiary, string $type, ?string $value, ?string $countryCode = null): void
    {
        if ($value) {
            $contact = new Contact();
            $contact->setBeneficiary($beneficiary)
                ->setContactTypeEnum($type)
                ->setValue($value);

            if ($countryCode && $type === 'phone') {
                $contact->setCountryCode($countryCode);
            }

            $this->entityManager->persist($contact);
        }
    }
}
