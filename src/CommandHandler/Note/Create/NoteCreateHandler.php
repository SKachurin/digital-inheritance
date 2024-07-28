<?php

declare(strict_types=1);

namespace App\CommandHandler\Note\Create;

use App\Entity\Note;
use App\Service\CryptoService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class NoteCreateHandler
{
    private ParameterBagInterface $params;
    private EntityManagerInterface $entityManager;

    public function __construct(ParameterBagInterface $params,EntityManagerInterface $entityManager)
    {
        $this->params = $params;
        $this->entityManager = $entityManager;
    }

    /**
     * @throws Exception
     */
    public function __invoke(NoteCreateInputDto $input): Note
    {
        $note = new Note();
        $personalString1 = $input->getCustomer()->getCustomerFirstQuestionAnswer();
        $personalString2 = $input->getCustomer()->getCustomerSecondQuestionAnswer();
        $cryptoService = null;

        $note->setCustomer($input->getCustomer());

        if ($personalString1 !== null) {
            $cryptoService = new CryptoService($this->params, $personalString1);
            $note
                ->setCustomerTextAnswerOne(
                    $cryptoService->encryptData(
                        $input->getCustomerText()
                    )
                );
        }

        if ($personalString2 !== null) {
            $cryptoService = new CryptoService($this->params, $personalString2);
            $note
                ->setCustomerTextAnswerTwo(
                    $cryptoService->encryptData(
                        $input->getCustomerText()
                    )
                );
        }
        $this->entityManager->persist($note);
        $this->entityManager->flush();

        return $note;
    }
}
