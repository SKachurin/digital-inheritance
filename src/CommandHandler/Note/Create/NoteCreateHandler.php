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
    public function __invoke(NoteCreateInputDto $input): Note
    {
        $note = new Note();
        $customer = $input->getCustomer();
        $note->setCustomer($customer);
        $cryptoService = null;

        $personalString1 = $this->cryptoService->decryptData(
            $customer->getCustomerFirstQuestionAnswer()
        );
        $personalString2 = $this->cryptoService->decryptData(
            $customer->getCustomerSecondQuestionAnswer()
        );
//        $personalString1 = $input->getCustomer()->getCustomerFirstQuestionAnswer();
//        $personalString2 = $input->getCustomer()->getCustomerSecondQuestionAnswer();


        if ($personalString1) {
            $cryptoService = new CryptoService($this->params, $this->logger, $personalString1);
            $note
                ->setCustomerTextAnswerOne(
                    $cryptoService->encryptData(
                        $input->getCustomerText()
                    )
                );
            //delete key after
//            $customer->setCustomerFirstQuestionAnswer(' ');
        }

        if ($personalString2) {
            $cryptoService = new CryptoService($this->params, $this->logger, $personalString2);
            $note
                ->setCustomerTextAnswerTwo(
                    $cryptoService->encryptData(
                        $input->getCustomerText()
                    )
                );
            //delete key after
//            $customer->setCustomerSecondQuestionAnswer(' ');
        }
        $this->entityManager->persist($note);
        $this->entityManager->persist($customer);
        $this->entityManager->flush();

        return $note;
    }
}
