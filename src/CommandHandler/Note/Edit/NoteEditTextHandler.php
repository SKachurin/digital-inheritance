<?php

namespace App\CommandHandler\Note\Edit;

use App\Entity\Note;
use App\Service\CryptoService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class NoteEditTextHandler
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CryptoService $crypto
    ) {}

    public function __invoke(NoteEditInputDto $in): NoteEditOutputDto
    {
        /** @var Note $note */
        $note = $this->em->getRepository(Note::class)->findOneBy(['customer' => $in->getCustomer()]);
        if (!$note instanceof Note) {
            throw new \UnexpectedValueException('Note not found for customer');
        }

        // choose which set to try: priority = Customer Q1 → Customer Q2 → Beneficiary Q1 → Beneficiary Q2
        $answer = $in->getCustomerFirstQuestionAnswer()
            ?? $in->getCustomerSecondQuestionAnswer()
            ?? $in->getBeneficiaryFirstQuestionAnswer()
            ?? $in->getBeneficiarySecondQuestionAnswer()
            ?? '';

        if ($answer === '') {
            throw new \InvalidArgumentException('Answer is required');
        }

        // Map the selected answer to the corresponding 3 replicas in the entity:
        $triples = [
            'c1' => [$note->getCustomerTextAnswerOne(), $note->getCustomerTextAnswerOneKms2(), $note->getCustomerTextAnswerOneKms3()],
            'c2' => [$note->getCustomerTextAnswerTwo(), $note->getCustomerTextAnswerTwoKms2(), $note->getCustomerTextAnswerTwoKms3()],
            'b1' => [$note->getBeneficiaryTextAnswerOne(), $note->getBeneficiaryTextAnswerOneKms2(), $note->getBeneficiaryTextAnswerOneKms3()],
            'b2' => [$note->getBeneficiaryTextAnswerTwo(), $note->getBeneficiaryTextAnswerTwoKms2(), $note->getBeneficiaryTextAnswerTwoKms3()],
        ];

        $selected = null;
        if ($in->getCustomerFirstQuestionAnswer())       $selected = $triples['c1'];
        elseif ($in->getCustomerSecondQuestionAnswer())  $selected = $triples['c2'];
        elseif ($in->getBeneficiaryFirstQuestionAnswer())$selected = $triples['b1'];
        elseif ($in->getBeneficiarySecondQuestionAnswer()) $selected = $triples['b2'];

        if ($selected === null) {
            throw new \InvalidArgumentException('No matching ciphertext set for the provided answer');
        }

        // Try 3 decrypts
        $plain = $this->crypto->decryptEnvelopeReplicas($selected, $note->getCustomer()->getId(), $answer);

        $out = new NoteEditOutputDto($in->getCustomer());

        if ($plain === false || $plain === null) {
            $out->setCustomerText(null);
            $out->setCustomerTextKMS2(null);
            $out->setCustomerTextKMS3(null);
        } else {
            // same decrypted text from any KMS replica
            $out->setCustomerText($plain);
            $out->setCustomerTextKMS2($plain);
            $out->setCustomerTextKMS3($plain);
        }

        // keep the questions/answers
        $out->setCustomerFirstQuestion($in->getCustomerFirstQuestion());
        $out->setCustomerSecondQuestion($in->getCustomerSecondQuestion());
        $out->setBeneficiaryFirstQuestion($in->getBeneficiaryFirstQuestion());
        $out->setBeneficiarySecondQuestion($in->getBeneficiarySecondQuestion());
        $out->setCustomerFirstQuestionAnswer($in->getCustomerFirstQuestionAnswer());
        $out->setCustomerSecondQuestionAnswer($in->getCustomerSecondQuestionAnswer());
        $out->setBeneficiaryFirstQuestionAnswer($in->getBeneficiaryFirstQuestionAnswer());
        $out->setBeneficiarySecondQuestionAnswer($in->getBeneficiarySecondQuestionAnswer());

        return $out;
    }
}
