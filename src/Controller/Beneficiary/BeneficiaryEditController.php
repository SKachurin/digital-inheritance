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
    public function __construct(
//        private MessageBusInterface $commandBus,
        private NoteRepository $noteRepository,
        private BeneficiaryRepository $beneficiaryRepository,
        private ContactRepository $contactRepository,
        private LoggerInterface $logger,
        private ParameterBagInterface $params,
        private EntityManagerInterface $entityManager
    )
    {
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

        $cryptoService = new CryptoService($this->params, $this->logger);
        $beneficiaryEmails = $this->contactRepository->findBy([
            'beneficiary' => $beneficiary,
            'contactTypeEnum' => 'email'
        ]);
        $beneficiaryPhones = $this->contactRepository->findBy([
            'beneficiary' => $beneficiary,
            'contactTypeEnum' => 'phone'
        ]);

        // Prepare the DTO with decrypted data
        $beneficiaryData = new BeneficiaryEditOutputDto();
        $beneficiaryData->setId($beneficiary->getId());
        $beneficiaryData->setBeneficiaryName($beneficiary->getBeneficiaryName());

        $beneficiaryData->setBeneficiaryFullName(
            $cryptoService->decryptData($beneficiary->getBeneficiaryFullName())
        );

        if (isset($beneficiaryEmails[0])) {
            $beneficiaryData->setBeneficiaryEmail(
                $cryptoService->decryptData(
                    $beneficiaryEmails[0]->getValue()
                )
            );
        }
        if (isset($beneficiaryEmails[1])) {
            $beneficiaryData->setBeneficiarySecondEmail(
                $cryptoService->decryptData(
                    $beneficiaryEmails[1]->getValue()
                )
            );
        }

        if (isset($beneficiaryPhones[0])) {
            $beneficiaryData->setBeneficiaryCountryCode(
                $beneficiaryPhones[0]->getCountryCode()
            );
            $beneficiaryData->setBeneficiaryFirstPhone(
                $cryptoService->decryptData(
                    $beneficiaryPhones[0]->getValue()
                )
            );
        }

        if (isset($beneficiaryPhones[1])) {
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
            /** @var BeneficiaryEditOutputDto $updatedData */
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
//     * @throws RandomException
//     * @throws \SodiumException
//     * @throws \Exception
//     */
//    public function edit(Request $request, int $beneficiaryId): Response
//    {
//        $customer = $this->getUser();
//
//        if (!$customer instanceof Customer) {
//            return $this->redirectToRoute('user_login');
//        }
//
//        $beneficiary = $this->beneficiaryRepository->find($beneficiaryId);
//
//        if (!$beneficiary) {
//            throw $this->createNotFoundException('Heir not found.');
//        }
//
//        $note = $this->noteRepository->findOneBy(['beneficiary' => $beneficiary, 'customer' => $customer]);
//
//        if (!$note) {
//            throw new AccessDeniedException('You do not have permission to edit this Heir.');
//        }
//
//        $beneficiaryData = new BeneficiaryEditOutputDto();
//        $cryptoService = new CryptoService($this->params, $this->logger);
//
//        // Populate the DTO with data
//        $beneficiaryEmails = $this->contactRepository->findBy([
//            'beneficiary' => $beneficiary,
//            'contactTypeEnum' => 'email'
//        ]);
//        $beneficiaryPhones = $this->contactRepository->findBy([
//            'beneficiary' => $beneficiary,
//            'contactTypeEnum' => 'phone'
//        ]);
//
//        $beneficiaryData->setBeneficiaryName($beneficiary->getBeneficiaryName());
//        $beneficiaryData->setBeneficiaryFullName(
//            $cryptoService->decryptData($beneficiary->getBeneficiaryFullName())
//        );
//
//        if (isset($beneficiaryEmails[0])) {
//            $beneficiaryData->setBeneficiaryEmail(
//                $cryptoService->decryptData($beneficiaryEmails[0]->getValue())
//            );
//        }
//        if (isset($beneficiaryEmails[1])) {
//            $beneficiaryData->setBeneficiarySecondEmail(
//                $cryptoService->decryptData($beneficiaryEmails[1]->getValue())
//            );
//        }
//
//        if (isset($beneficiaryPhones[0])) {
//            $beneficiaryData->setBeneficiaryCountryCode($beneficiaryPhones[0]->getCountryCode());
//            $beneficiaryData->setBeneficiaryFirstPhone(
//                $cryptoService->decryptData($beneficiaryPhones[0]->getValue())
//            );
//        }
//        if (isset($beneficiaryPhones[1])) {
//            $beneficiaryData->setBeneficiarySecondPhone(
//                $cryptoService->decryptData($beneficiaryPhones[1]->getValue())
//            );
//        }
//
//        $form = $this->createForm(BeneficiaryEditType::class, $beneficiaryData);
//        $form->handleRequest($request);
//
//        if ($form->isSubmitted() && $form->isValid()) {
//            /** @var BeneficiaryEditInputDto $updatedData */
//            $updatedData = $form->getData();
//
//            $beneficiary->setBeneficiaryName($updatedData->getBeneficiaryName());
//            $beneficiary->setBeneficiaryFullName(
//                $cryptoService->encryptData($updatedData->getBeneficiaryFullName())
//            );
//
//            $this->updateContacts($beneficiary, $updatedData, $cryptoService);
//            $this->entityManager->flush();
//
//            $this->addFlash('success', 'Your Heir has been updated.');
//
//            // Redirect to avoid form resubmission
//            return $this->redirectToRoute('beneficiary_edit', ['beneficiaryId' => $beneficiaryId]);
//        }
//
//        return $this->render('beneficiaryEdit.html.twig', [
//            'form' => $form->createView(),
//        ]);
//    }

    /**
     * @throws RandomException
     * @throws \SodiumException
     */
    private function updateContacts(Beneficiary $beneficiary, BeneficiaryEditOutputDto $updatedData, CryptoService $cryptoService): void
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
