<?php

declare(strict_types=1);

namespace App\CommandHandler\Note\Decrypt;

use App\Service\CryptoService;
use App\Service\Kms\KmsRateLimitedExceptionService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class NoteDecryptHandler
{
    public function __construct(
        private readonly CryptoService $crypto
    ) {}

    public function __invoke(NoteDecryptInputDto $input): NoteDecryptOutputDto
    {
        $out = new NoteDecryptOutputDto($input->getCustomer());
        $out->setCustomer($input->getCustomer());

        $customerId = (int) $input->getCustomer()->getId();
        $rateLimitSeconds = null;

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
        ];

        $chosenTriplet   = null; // encrypted triplet used for display fallback
        $chosenSlots     = null; // plaintext per slot [0..2]
        $fallbackTriplet = null; // last-seen non-empty triplet from DB

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
                    // DO NOT break early from slot loop if you want — but here we only need success flag.
                    // We keep full $slots anyway.
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
            // overlay plaintext where available; keep fallback where not
            if (is_string($chosenSlots[0] ?? null) && $chosenSlots[0] !== '') { $v0 = $chosenSlots[0]; $anySuccess = true; }
            if (is_string($chosenSlots[1] ?? null) && $chosenSlots[1] !== '') { $v1 = $chosenSlots[1]; $anySuccess = true; }
            if (is_string($chosenSlots[2] ?? null) && $chosenSlots[2] !== '') { $v2 = $chosenSlots[2]; $anySuccess = true; }
        }

        $out->setCustomerText($v0);
        $out->setCustomerTextKMS2($v1);
        $out->setCustomerTextKMS3($v2);
        $out->setDecryptionSucceeded($anySuccess);

        return $out;
    }
}