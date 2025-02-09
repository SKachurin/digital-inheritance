<?php

declare(strict_types=1);

namespace App\CommandHandler\Note\Decrypt;

use App\CommandHandler\Note\Create\NoteCreateInputDto;
use App\Entity\Note;
use App\Service\CryptoService;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class NoteDecryptHandler  // TODO Looks like legacy to me
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
    public function __invoke(NoteDecryptInputDto $input): NoteDecryptOutputDto
    {
        $note = new NoteDecryptOutputDto($input->getCustomer());
        $cryptoService = null;
        $note->setCustomer($input->getCustomer());

        if ($input->getCustomerTextAnswerOne() !== null) {
            $personalString = $input->getCustomerFirstQuestionAnswer();
//            $personalStringDecrypted = $personalString;//$this->cryptoService->decryptData($personalString);

            $cryptoService = new CryptoService($this->params, $this->logger, $personalString);

//            $this->logger->info('getCustomerTextAnswerOne() !== null)  decryptedText.', ['$input->getCustomerTextAnswerOne()' => $input->getCustomerTextAnswerOne()]);

            $decryptedText = $cryptoService->decryptData($input->getCustomerTextAnswerOne());

            if ($decryptedText === false) {
                // Handle decryption failure for Customer Text Answer One
                throw new \RuntimeException('Decryption failed for CustomerTextAnswerOne.');
            }
            $note->setCustomerText($decryptedText);
        }

        if ($input->getCustomerTextAnswerTwo() !== null) {
            $personalString = $input->getCustomerSecondQuestionAnswer();
//            $personalStringDecrypted = $personalString; //$this->cryptoService->decryptData($personalString);

//            $this->logger->info(' getCustomerTextAnswerTwo() !== null getCustomerTextAnswerTwo.', ['$personalString' => $personalString]);
//            $this->logger->info('getCustomerTextAnswerTwo() !== null   decryptedText.', ['$input->getCustomerTextAnswerTwo()' => $input->getCustomerTextAnswerTwo()]);

            $cryptoService = new CryptoService($this->params, $this->logger, $personalString);

            $decryptedText = $cryptoService->decryptData($input->getCustomerTextAnswerTwo());

            if ($decryptedText === false) {
                // Handle decryption failure for Customer Text Answer One
                throw new \RuntimeException('Decryption failed for CustomerTextAnswerTwo.');
            }
            $note->setCustomerText($decryptedText);

        }

        return $note;
    }
}
