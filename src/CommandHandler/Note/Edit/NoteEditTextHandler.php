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
            ->setCustomerFirstQuestionAnswer(
                $this->cryptoService->encryptData(
                    $input->getCustomerFirstQuestionAnswer()
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
        if ($input->getCustomerSecondQuestionAnswer()) {
            $customer
                ->setCustomerSecondQuestionAnswer(
                    $this->cryptoService->encryptData(
                        $input->getCustomerSecondQuestionAnswer()
                    )
                );
        }
        $this->entityManager->persist($customer);
        $this->entityManager->flush();

//        $cryptoService = null;

        $personalString1 = $this->cryptoService->decryptData(
            $customer->getCustomerFirstQuestionAnswer()
        );
        $personalString2 = $this->cryptoService->decryptData(
            $customer->getCustomerSecondQuestionAnswer()
        );

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
        $this->entityManager->persist($note);
//        $this->entityManager->persist($customer);
        $this->entityManager->flush();

        return $note;
    }
}
