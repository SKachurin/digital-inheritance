<?php

declare(strict_types=1);

namespace App\CommandHandler\Note\Edit;

use App\Entity\Note;
use App\Service\CryptoService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class NoteEditTextHandler
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CryptoService $cryptoService,
    ) {}

    public function __invoke(NoteEditTextInputDto $input): Note
    {
        $note = $input->getNote();
        $customer = $note->getCustomer();

        // Encrypt questions (legacy CryptoService) – same as create handler
        $customer->setCustomerFirstQuestion(
            $this->cryptoService->encryptData($input->getCustomerFirstQuestion())
        );

        if ($input->getCustomerSecondQuestion()) {
            $customer->setCustomerSecondQuestion(
                $this->cryptoService->encryptData($input->getCustomerSecondQuestion())
            );
        } else {
            $customer->setCustomerSecondQuestion(null);
        }

        $beneficiary = $note->getBeneficiary();
        if ($beneficiary) {
            if ($input->getBeneficiaryFirstQuestion()) {
                $beneficiary->setBeneficiaryFirstQuestion(
                    $this->cryptoService->encryptData($input->getBeneficiaryFirstQuestion())
                );
            }

            if ($input->getBeneficiarySecondQuestion()) {
                $beneficiary->setBeneficiarySecondQuestion(
                    $this->cryptoService->encryptData($input->getBeneficiarySecondQuestion())
                );
            } else {
                $beneficiary->setBeneficiarySecondQuestion(null);
            }
        }

        // Persist blobs EXACTLY like create flow.
        // Convert "" -> null so missing KMS replicas stay NULL in DB.
        $n = static fn(?string $v): ?string => (is_string($v) && trim($v) !== '') ? $v : null;

        $note->setCustomerTextAnswerOne($n($input->getCustomerTextAnswerOne()));
        $note->setCustomerTextAnswerOneKms2($n($input->getCustomerTextAnswerOneKms2()));
        $note->setCustomerTextAnswerOneKms3($n($input->getCustomerTextAnswerOneKms3()));

        $note->setCustomerTextAnswerTwo($n($input->getCustomerTextAnswerTwo()));
        $note->setCustomerTextAnswerTwoKms2($n($input->getCustomerTextAnswerTwoKms2()));
        $note->setCustomerTextAnswerTwoKms3($n($input->getCustomerTextAnswerTwoKms3()));

        if ($beneficiary) {
            $note->setBeneficiaryTextAnswerOne($n($input->getBeneficiaryTextAnswerOne()));
            $note->setBeneficiaryTextAnswerOneKms2($n($input->getBeneficiaryTextAnswerOneKms2()));
            $note->setBeneficiaryTextAnswerOneKms3($n($input->getBeneficiaryTextAnswerOneKms3()));

            $note->setBeneficiaryTextAnswerTwo($n($input->getBeneficiaryTextAnswerTwo()));
            $note->setBeneficiaryTextAnswerTwoKms2($n($input->getBeneficiaryTextAnswerTwoKms2()));
            $note->setBeneficiaryTextAnswerTwoKms3($n($input->getBeneficiaryTextAnswerTwoKms3()));
        }

        $this->em->persist($customer);
        if ($beneficiary) {
            $this->em->persist($beneficiary);
        }
        $this->em->persist($note);
        $this->em->flush();

        return $note;
    }
}