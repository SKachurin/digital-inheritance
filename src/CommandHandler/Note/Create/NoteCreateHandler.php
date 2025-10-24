<?php

declare(strict_types=1);

namespace App\CommandHandler\Note\Create;

use App\Entity\Note;
use App\Service\CryptoService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Psr\Log\LoggerInterface;

#[AsMessageHandler]
class NoteCreateHandler
{
//    private ParameterBagInterface $params;
//    private EntityManagerInterface $entityManager;
//    private CryptoService $cryptoService;
//    private LoggerInterface $logger;

    public function __construct(
        private ParameterBagInterface $params,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
        private CryptoService $cryptoService
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
    public function __invoke(NoteCreateInputDto $input): Note
    {
        $customer = $input->getCustomer();

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

        $note = new Note();
        $note->setCustomer($customer);
        $note->setBeneficiary($beneficiary);
        $cryptoService = null;

        $encryptIfNeeded = function (?string $personalString, callable $setter) use ($input) {
            if (!$personalString) {
                return;
            }

            if ($input->isFrontendEncrypted()) {
                $setter($input->getCustomerText());
            } else {
                $crypto = new CryptoService($this->params, $this->logger, $personalString);
                $setter($crypto->encryptData($input->getCustomerText()));
            }
        };

        $encryptIfNeeded($input->getCustomerFirstQuestionAnswer(), fn($v) => $note->setCustomerTextAnswerOne($v));
        $encryptIfNeeded($input->getCustomerSecondQuestionAnswer(), fn($v) => $note->setCustomerTextAnswerTwo($v));
        $encryptIfNeeded($input->getBeneficiaryFirstQuestionAnswer(), fn($v) => $note->setBeneficiaryTextAnswerOne($v));
        $encryptIfNeeded($input->getBeneficiarySecondQuestionAnswer(), fn($v) => $note->setBeneficiaryTextAnswerTwo($v));

//        $personalString1 = $input->getCustomerFirstQuestionAnswer();
//        $personalString2 = $input->getCustomerSecondQuestionAnswer();
//        $personalString3 = $input->getBeneficiaryFirstQuestionAnswer();
//        $personalString4 = $input->getBeneficiarySecondQuestionAnswer();

//        if ($personalString1 && !$input->isFrontendEncrypted()) {
//            $cryptoService = new CryptoService($this->params, $this->logger, $personalString1);
//            $note
//                ->setCustomerTextAnswerOne(
//                    $cryptoService->encryptData(
//                        $input->getCustomerText()
//                    )
//                )
//            ;
//        } elseif ($personalString1) {
//            $note
//                ->setCustomerTextAnswerOne(
//                    $input->getCustomerText()
//                )
//            ;
//        }
//
//        if ($personalString2 && !$input->isFrontendEncrypted()) {
//            $cryptoService = new CryptoService($this->params, $this->logger, $personalString2);
//            $note
//                ->setCustomerTextAnswerTwo(
//                    $cryptoService->encryptData(
//                        $input->getCustomerText()
//                    )
//                )
//            ;
//        } elseif ($personalString2) {
//            $note
//                ->setCustomerTextAnswerTwo(
//                    $input->getCustomerText()
//                )
//            ;
//        }
//
//        if ($personalString3 && !$input->isFrontendEncrypted()) {
//            $cryptoService = new CryptoService($this->params, $this->logger, $personalString3);
//            $note
//                ->setBeneficiaryTextAnswerOne(
//                    $cryptoService->encryptData(
//                        $input->getCustomerText()
//                    )
//                )
//            ;
//        } elseif ($personalString3) {
//            $note
//                ->setBeneficiaryTextAnswerOne(
//                    $input->getCustomerText()
//                )
//            ;
//        }
//
//        if ($personalString4 && !$input->isFrontendEncrypted()) {
//            $cryptoService = new CryptoService($this->params, $this->logger, $personalString4);
//            $note
//                ->setBeneficiaryTextAnswerTwo(
//                    $cryptoService->encryptData(
//                        $input->getCustomerText()
//                    )
//                )
//            ;
//        } elseif ($personalString4) {
//            $note
//                ->setBeneficiaryTextAnswerTwo(
//                    $input->getCustomerText()
//                )
//            ;
//        }

        $this->entityManager->persist($note);
        $this->entityManager->flush();

        return $note;
    }
}
