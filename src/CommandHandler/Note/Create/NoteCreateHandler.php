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
    public function __construct(
        private ParameterBagInterface $params,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
        private CryptoService $cryptoService
    ) {}

    /**
     * @throws Exception
     */
    public function __invoke(NoteCreateInputDto $input): Note
    {
        $customer = $input->getCustomer();

        // Encrypt customer questions (legacy encryption)
        $customer->setCustomerFirstQuestion(
            $this->cryptoService->encryptData($input->getCustomerFirstQuestion())
        );

        if ($input->getCustomerSecondQuestion()) {
            $customer->setCustomerSecondQuestion(
                $this->cryptoService->encryptData($input->getCustomerSecondQuestion())
            );
        }

        // Beneficiary (legacy)
        $beneficiary = $customer->getBeneficiary()[0];

        $beneficiary->setBeneficiaryFirstQuestion(
            $this->cryptoService->encryptData($input->getBeneficiaryFirstQuestion())
        );

        if ($input->getCustomerSecondQuestion()) {
            $beneficiary->setBeneficiarySecondQuestion(
                $this->cryptoService->encryptData($input->getBeneficiarySecondQuestion())
            );
        }

        $this->entityManager->persist($customer);
        $this->entityManager->persist($beneficiary);

        // Create new Note
        $note = new Note();
        $note->setCustomer($customer);
        $note->setBeneficiary($beneficiary);

        // Closure for applying personal encryption
        $note->setCustomerTextAnswerOne($input->getCustomerTextAnswerOne());
        $note->setCustomerTextAnswerOneKms2($input->getCustomerTextAnswerOneKms2());
        $note->setCustomerTextAnswerOneKms3($input->getCustomerTextAnswerOneKms3());

        $note->setCustomerTextAnswerTwo($input->getCustomerTextAnswerTwo());
        $note->setCustomerTextAnswerTwoKms2($input->getCustomerTextAnswerTwoKms2());
        $note->setCustomerTextAnswerTwoKms3($input->getCustomerTextAnswerTwoKms3());

        $note->setBeneficiaryTextAnswerOne($input->getBeneficiaryTextAnswerOne());
        $note->setBeneficiaryTextAnswerOneKms2($input->getBeneficiaryTextAnswerOneKms2());
        $note->setBeneficiaryTextAnswerOneKms3($input->getBeneficiaryTextAnswerOneKms3());

        $note->setBeneficiaryTextAnswerTwo($input->getBeneficiaryTextAnswerTwo());
        $note->setBeneficiaryTextAnswerTwoKms2($input->getBeneficiaryTextAnswerTwoKms2());
        $note->setBeneficiaryTextAnswerTwoKms3($input->getBeneficiaryTextAnswerTwoKms3());

        $this->entityManager->persist($note);
        $this->entityManager->flush();

        return $note;
    }
}
