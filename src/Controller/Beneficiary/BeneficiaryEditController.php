<?php
namespace App\Controller\Beneficiary;

use App\CommandHandler\Beneficiary\Edit\BeneficiaryEditInputDto;
use App\Entity\Customer;
use App\Form\Type\BeneficiaryEditType;
use App\Repository\BeneficiaryRepository;
use App\Repository\ContactRepository;
use App\Repository\NoteRepository;
use App\Service\CryptoService;
use Psr\Log\LoggerInterface;
use Random\RandomException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;

class BeneficiaryEditController extends AbstractController
{
    public function __construct(
        private MessageBusInterface   $commandBus,
        private NoteRepository        $noteRepository,
        private BeneficiaryRepository $beneficiaryRepository,
        private ContactRepository     $contactRepository,
        private LoggerInterface       $logger,
        private ParameterBagInterface $params,
        private TranslatorInterface   $translator
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

        $beneficiary = $this->beneficiaryRepository->find($beneficiaryId);

        if (!$beneficiary) {
            throw $this->createNotFoundException('Heir not found.');
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
        $beneficiaryData = new BeneficiaryEditInputDto();
        $beneficiaryData->setId($beneficiary->getId());
        $beneficiaryData->setBeneficiaryName($beneficiary->getBeneficiaryName());

        $beneficiaryData->setBeneficiaryFullName(
            $cryptoService->decryptData($beneficiary->getBeneficiaryFullName())
        );

        $beneficiaryData->setCustomerFullName(
            $cryptoService->decryptData(
                $beneficiary->getCustomer()->getCustomerFullName()
            )
        )->setBeneficiaryLang($beneficiary->getBeneficiaryLang());

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
            /** @var BeneficiaryEditInputDto $updatedData */
            $updatedData = $form->getData();

            $this->commandBus->dispatch($updatedData);

            $this->addFlash('success', $this->translator->trans('errors.flash.heir_updated'));
            return $this->redirectToRoute('user_home');
        }

        return $this->render('beneficiary/beneficiaryEdit.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
