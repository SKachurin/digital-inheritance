<?php

declare(strict_types=1);

namespace App\CommandHandler\Note\Decrypt;

use App\Service\CryptoService;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class BeneficiaryNoteDecryptHandler
{
    public function __construct(
        private ParameterBagInterface $params,
        private LoggerInterface       $logger
    )
    {}

    /**
     * @throws Exception
     */
    public function __invoke(BeneficiaryNoteDecryptInputDto $input): BeneficiaryNoteDecryptOutputDto
    {
        $note = new BeneficiaryNoteDecryptOutputDto($input->getNote());
        $cryptoService = null;
        $note->setNote($input->getNote());

        $decryptedText = null;

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
        }

        $note->setCustomerText($decryptedText);

        $note->setBeneficiaryFirstQuestion($input->getBeneficiaryFirstQuestion());
        $note->setBeneficiarySecondQuestion($input->getBeneficiarySecondQuestion());

        return $note;
    }
}
