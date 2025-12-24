<?php

declare(strict_types=1);

namespace App\CommandHandler\Note\Decrypt;

use App\Service\CryptoService;
use App\Service\Kms\KmsRateLimitedExceptionService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class BeneficiaryNoteDecryptHandler
{
    public function __construct(
        private readonly CryptoService $crypto
    ) {}

    public function __invoke(BeneficiaryNoteDecryptInputDto $input): BeneficiaryNoteDecryptOutputDto
    {
        $out = new BeneficiaryNoteDecryptOutputDto($input->getNote());
        $out->setNote($input->getNote());

        $customerId = (int) $input->getNote()->getCustomer()?->getId();
        if ($customerId <= 0) {
            // keep behavior predictable; if no customer id, nothing can be decrypted in KMS flow
            $out->setCustomerText(null);
            $out->setCustomerTextKMS2(null);
            $out->setCustomerTextKMS3(null);
            $out->setDecryptionSucceeded(false);
            return $out;
        }

        $rateLimitSeconds = null;

        $attempts = [
            [
                'answer'  => $input->getBeneficiaryFirstQuestionAnswer(),
                'triplet' => [
                    $input->getBeneficiaryTextAnswerOne(),
                    $input->getBeneficiaryTextAnswerOneKms2(),
                    $input->getBeneficiaryTextAnswerOneKms3(),
                ],
            ],
            [
                'answer'  => $input->getBeneficiarySecondQuestionAnswer(),
                'triplet' => [
                    $input->getBeneficiaryTextAnswerTwo(),
                    $input->getBeneficiaryTextAnswerTwoKms2(),
                    $input->getBeneficiaryTextAnswerTwoKms3(),
                ],
            ],
        ];

        $chosenTriplet   = null;
        $chosenSlots     = null;
        $fallbackTriplet = null;

        foreach ($attempts as $attempt) {
            $answer  = $attempt['answer'];
            $triplet = $attempt['triplet'];

            if (!array_filter($triplet)) {
                continue;
            }

            $fallbackTriplet = $triplet;

            if ($answer === null || $answer === '') {
                continue;
            }

            try {
                $slots = $this->crypto->decryptEnvelopeTripletForUi($triplet, $customerId, $answer);
            } catch (KmsRateLimitedExceptionService $e) {
                $rateLimitSeconds = $e->getRetryAfterSeconds();
                break;
            }

            $answerHasSuccess = false;
            foreach ([0, 1, 2] as $i) {
                $plain = $slots[$i] ?? null;
                if (is_string($plain) && $plain !== '') {
                    $answerHasSuccess = true;
                }
            }

            if ($answerHasSuccess) {
                $chosenTriplet = $triplet;
                $chosenSlots   = $slots;
                break;
            }
        }

        if ($rateLimitSeconds !== null) {
            $out->setRateLimitSeconds($rateLimitSeconds);
        }

        $displayTriplet = $chosenTriplet ?? $fallbackTriplet ?? [null, null, null];

        $v0 = $displayTriplet[0] ?? null;
        $v1 = $displayTriplet[1] ?? null;
        $v2 = $displayTriplet[2] ?? null;

        $anySuccess = false;

        if ($chosenSlots !== null) {
            if (is_string($chosenSlots[0] ?? null) && $chosenSlots[0] !== '') { $v0 = $chosenSlots[0]; $anySuccess = true; }
            if (is_string($chosenSlots[1] ?? null) && $chosenSlots[1] !== '') { $v1 = $chosenSlots[1]; $anySuccess = true; }
            if (is_string($chosenSlots[2] ?? null) && $chosenSlots[2] !== '') { $v2 = $chosenSlots[2]; $anySuccess = true; }
        }

        $out->setCustomerText($v0);
        $out->setCustomerTextKMS2($v1);
        $out->setCustomerTextKMS3($v2);
        $out->setDecryptionSucceeded($anySuccess);

        $out->setBeneficiaryFirstQuestion($input->getBeneficiaryFirstQuestion());
        $out->setBeneficiarySecondQuestion($input->getBeneficiarySecondQuestion());

        return $out;
    }
}