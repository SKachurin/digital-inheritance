<?php

declare(strict_types=1);

namespace App\CommandHandler\Note\Edit;

use App\Service\CryptoService;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class NoteEditHandler
{
    private ParameterBagInterface $params;
    private LoggerInterface $logger;


    public function __construct(
        ParameterBagInterface $params,
        LoggerInterface $logger
    )
    {
        $this->params = $params;
        $this->logger = $logger;
    }

    /**
     * @throws Exception
     */
    public function __invoke(NoteEditInputDto $input): NoteEditOutputDto
    {
        $note = new NoteEditOutputDto($input->getCustomer());
        $cryptoService = null;
        $note->setCustomer($input->getCustomer());

        $decryptedText = null;

        if ($input->getCustomerFirstQuestionAnswer() !== null) {
            $personalString = $input->getCustomerFirstQuestionAnswer();
            $cryptoService = new CryptoService($this->params, $this->logger, $personalString);
            $decryptedText = $cryptoService->decryptData($input->getCustomerTextAnswerOne());
        }

        if ($decryptedText === null && $input->getCustomerSecondQuestionAnswer() !== null) {
            $personalString = $input->getCustomerSecondQuestionAnswer();
            $cryptoService = new CryptoService($this->params, $this->logger, $personalString);
            $decryptedText = $cryptoService->decryptData($input->getCustomerTextAnswerTwo());
        }

        if ($decryptedText === null && $input->getBeneficiaryFirstQuestionAnswer() !== null) {
            $personalString = $input->getBeneficiaryFirstQuestionAnswer();
            $cryptoService = new CryptoService($this->params, $this->logger, $personalString);
            $decryptedText = $cryptoService->decryptData($input->getBeneficiaryTextAnswerOne());
        }

        if ($decryptedText === null && $input->getBeneficiarySecondQuestionAnswer() !== null) {
            $personalString = $input->getBeneficiarySecondQuestionAnswer();
            $cryptoService = new CryptoService($this->params, $this->logger, $personalString);
            $decryptedText = $cryptoService->decryptData($input->getBeneficiaryTextAnswerTwo());
        }


        if ($decryptedText === false) {
            $decryptedText = null;
            // TODO
            // Set limit in DB for each answer - total 5 attempts\
            // or
            // Set timer x2 for each next decryption try. From 30 sec. + sent email "Is it you? or Would you like to change pass for the App."
        }

        $note->setCustomerText($decryptedText);

        return $note;
    }
}
