<?php

declare(strict_types=1);

namespace App\CommandHandler\Note\Edit;

use App\CommandHandler\Note\Create\NoteCreateInputDto;
use App\Entity\Note;
use App\Service\CryptoService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Psr\Log\LoggerInterface;

#[AsMessageHandler]
class NoteEditTextHandler
{
    private LoggerInterface $logger;
    private ParameterBagInterface $params;
    private EntityManagerInterface $entityManager;
    private CryptoService $cryptoService;

    public function __construct(
        ParameterBagInterface $params,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        CryptoService $cryptoService
    )
    {
        $this->params = $params;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->cryptoService = $cryptoService;
    }

    /**
     * @throws Exception
     */
    public function __invoke(NoteEditTextInputDto $input): Note
    {
        $note = $input->getNote();
        $customer = $note->getCustomer();

        $customer
            ->setCustomerFirstQuestion(
                $this->cryptoService->encryptData(
                    $input->getCustomerFirstQuestion()
                )
            )
        ;
        if ($input->getCustomerSecondQuestion()) {
            $customer
                ->setCustomerSecondQuestion(
                    $this->cryptoService->encryptData(
                        $input->getCustomerSecondQuestion()
                    )
                )
            ;
        }

        $beneficiaries = $customer->getBeneficiary();
        $beneficiary = $beneficiaries[0];

        $beneficiary
            ->setBeneficiaryFirstQuestion(
                $this->cryptoService->encryptData(
                    $input->getBeneficiaryFirstQuestion()
                )
            )
        ;

        if ($input->getCustomerSecondQuestion()) {
            $beneficiary
                ->setBeneficiarySecondQuestion(
                    $this->cryptoService->encryptData(
                        $input->getBeneficiarySecondQuestion()
                    )
                )
            ;
        }

        $this->entityManager->persist($customer);
        $this->entityManager->persist($beneficiary);
//        $this->entityManager->flush();

        $note->setCustomer($customer);
        $note->setBeneficiary($beneficiary);
        $cryptoService = null;

        $personalString1 = $input->getCustomerFirstQuestionAnswer();
        $personalString2 = $input->getCustomerSecondQuestionAnswer();
        $personalString3 = $input->getBeneficiaryFirstQuestionAnswer();
        $personalString4 = $input->getBeneficiarySecondQuestionAnswer();

        if ($personalString1) {
            $cryptoService = new CryptoService($this->params, $this->logger, $personalString1);
            $note
                ->setCustomerTextAnswerOne(
                    $cryptoService->encryptData(
                        $input->getCustomerText()
                    )
                )
            ;
        }

        if ($personalString2) {
            $cryptoService = new CryptoService($this->params, $this->logger, $personalString2);
            $note
                ->setCustomerTextAnswerTwo(
                    $cryptoService->encryptData(
                        $input->getCustomerText()
                    )
                )
            ;
        }

        if ($personalString3) {
            $cryptoService = new CryptoService($this->params, $this->logger, $personalString3);
            $note
                ->setBeneficiaryTextAnswerOne(
                    $cryptoService->encryptData(
                        $input->getCustomerText()
                    )
                )
            ;
        }

        if ($personalString4) {
            $cryptoService = new CryptoService($this->params, $this->logger, $personalString4);
            $note
                ->setBeneficiaryTextAnswerTwo(
                    $cryptoService->encryptData(
                        $input->getCustomerText()
                    )
                )
            ;
        }

        $this->entityManager->persist($note);
        $this->entityManager->flush();

        return $note;
    }
}
