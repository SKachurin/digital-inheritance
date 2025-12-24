<?php

declare(strict_types=1);

namespace App\CommandHandler\Note\Edit;

use App\Service\CryptoService;
use App\Service\Kms\KmsRateLimitedExceptionService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class NoteEditTextHandler
{
    public function __construct(
        private readonly CryptoService $crypto
    ) {}

    public function __invoke(NoteEditInputDto $input): NoteEditOutputDto
    {
        $out = new NoteEditOutputDto($input->getCustomer());
        $out->setCustomer($input->getCustomer());

        $customerId = (int) $input->getCustomer()->getId();
        $rateLimitSeconds = null;

        // Selected answer result (3 slots). We only fill these once we found an answer that works.
        $selectedTriplet = null;   // original encrypted triplet
        $selectedSlots   = null;   // decrypted per slot (strings or null/empty depending on your CryptoService)
        $anySuccess      = false;

        $attempts = [
            [
                'answer'  => $input->getCustomerFirstQuestionAnswer(),
                'triplet' => [
                    $input->getCustomerTextAnswerOne(),
                    $input->getCustomerTextAnswerOneKms2(),
                    $input->getCustomerTextAnswerOneKms3(),
                ],
            ],
            [
                'answer'  => $input->getCustomerSecondQuestionAnswer(),
                'triplet' => [
                    $input->getCustomerTextAnswerTwo(),
                    $input->getCustomerTextAnswerTwoKms2(),
                    $input->getCustomerTextAnswerTwoKms3(),
                ],
            ],
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

        foreach ($attempts as $attempt) {
            $answer  = $attempt['answer'];
            $triplet = $attempt['triplet'];

            if (!$answer || !array_filter($triplet)) {
                continue;
            }

            try {
                $slots = $this->crypto->decryptEnvelopeTripletForUi(
                    $triplet,
                    $customerId,
                    $answer
                );
            } catch (KmsRateLimitedExceptionService $e) {
                $rateLimitSeconds = $e->getRetryAfterSeconds();
                break;
            }

            // IMPORTANT: do NOT stop on first success inside the 3 slots.
            // We only decide whether this ANSWER is acceptable after scanning all 3.
            $answerHasSuccess = false;
            foreach ($slots as $plain) {
                if (is_string($plain) && $plain !== '') {
                    $answerHasSuccess = true;
                    break;
                }
            }

            if ($answerHasSuccess) {
                // We accept THIS answer, and we keep all 3 slot results (so UI can show all three).
                $selectedTriplet = $triplet;
                $selectedSlots   = $slots;
                $anySuccess      = true;
                break; // stop trying other answers, but we already have all 3 slots for this answer
            }

            // if this answer had zero success -> try next answer
        }

        if ($rateLimitSeconds !== null) {
            $out->setRateLimitSeconds($rateLimitSeconds);
        }

        // Now map 3 outputs for UI.
        // RULE:
        // - if anySuccess: show plaintext per slot where decrypted, and fallback encrypted JSON (original triplet slot) where not
        // - if no success: show fallbacks (or null) everywhere
        if ($selectedTriplet === null) {
            $selectedTriplet = [null, null, null];
        }
        if ($selectedSlots === null) {
            $selectedSlots = [null, null, null];
        }

        // Adjust these setters to your real OutputDto API:
        // KMS1
        $out->setCustomerText(
            ($anySuccess && is_string($selectedSlots[0]) && $selectedSlots[0] !== '')
                ? $selectedSlots[0]
                : ($selectedTriplet[0] ?? null)
        );

        // KMS2
        $out->setCustomerTextKms2(
            ($anySuccess && is_string($selectedSlots[1]) && $selectedSlots[1] !== '')
                ? $selectedSlots[1]
                : ($selectedTriplet[1] ?? null)
        );

        // KMS3
        $out->setCustomerTextKms3(
            ($anySuccess && is_string($selectedSlots[2]) && $selectedSlots[2] !== '')
                ? $selectedSlots[2]
                : ($selectedTriplet[2] ?? null)
        );

        // Optional: if you have a boolean field, set it here.
        // $out->setDecryptionSucceeded($anySuccess);

        // questions are metadata, always pass through
        $out->setCustomerFirstQuestion($input->getCustomerFirstQuestion());
        $out->setCustomerSecondQuestion($input->getCustomerSecondQuestion());
        $out->setBeneficiaryFirstQuestion($input->getBeneficiaryFirstQuestion());
        $out->setBeneficiarySecondQuestion($input->getBeneficiarySecondQuestion());

        return $out;
    }
}