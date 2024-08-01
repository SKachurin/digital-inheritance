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

        if ($input->getCustomerFirstQuestionAnswer() !== null) {
            $personalString = $input->getCustomerFirstQuestionAnswer();
//            $personalStringDecrypted = $personalString;//$this->cryptoService->decryptData($personalString);

            $cryptoService = new CryptoService($this->params, $this->logger, $personalString);

//            $this->logger->info('getCustomerTextAnswerOne() !== null)  decryptedText.', ['$input->getCustomerTextAnswerOne()' => $input->getCustomerTextAnswerOne()]);

            $decryptedText = $cryptoService->decryptData($input->getCustomerTextAnswerOne());

            if ($decryptedText === false) {
                // Handle decryption failure
//                throw new \RuntimeException('Decryption failed for CustomerTextAnswerOne.');
                $decryptedText = null;
                //TODO Set timer x2 for each next decryption try. From 30 sec. + sent email "Is it you? or Would you like to change pass for the App."
            }
            $note->setCustomerText($decryptedText);
        }

        return $note;
    }
}
