<?php

declare(strict_types=1);

namespace App\CommandHandler\Note\Edit;

use App\Service\CryptoService;
use App\Service\Api\KmsRateLimitedExceptionService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class NoteEditHandler
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
        $decryptionSucceeded = false;

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

        // What we display against / overlay
        $chosenTriplet   = null; // encrypted json triplet we display against (attempted triplet)
        $chosenSlots     = null; // plaintext overlay [0..2] (nulls where not decrypted)
        $fallbackTriplet = null; // first-seen triplet that exists in DB (encrypted-only fallback)

        // 1) Pick the first FILLED answer (strict priority order).
        $selectedAnswer = null;
        $selectedTriplet = null;

        foreach ($attempts as $attempt) {
            $answer = $attempt['answer'];
            if ($answer === null || $answer === '') {
                continue;
            }

            $selectedAnswer  = $answer;
            $selectedTriplet = $attempt['triplet'];
            break;
        }

        // 2) Choose fallback triplet for display (first triplet that exists in DB).
        foreach ($attempts as $attempt) {
            if (array_filter($attempt['triplet'])) {
                $fallbackTriplet = $attempt['triplet'];
                break;
            }
        }

        // 3) Attempt decrypt ONCE (only for the selected answer).
        if ($selectedAnswer !== null && $selectedTriplet !== null) {
            // Prefer showing the attempted triplet (even if decrypt fails)
            $chosenTriplet = $selectedTriplet;

            try {
                // returns [0=>?plain, 1=>?plain, 2=>?plain]
                $slots = $this->crypto->decryptEnvelopeTripletForUi($selectedTriplet, $customerId, $selectedAnswer);

                $anyPlain = !empty($slots[0]) || !empty($slots[1]) || !empty($slots[2]);

                if ($anyPlain) {
                    $chosenSlots = $slots;
                    $decryptionSucceeded = true;
                }
            } catch (KmsRateLimitedExceptionService $e) {
                $rateLimitSeconds = $e->getRetryAfterSeconds();
            } catch (\SodiumException $e) {
            }
        }

        $displayTriplet = $chosenTriplet ?? $fallbackTriplet ?? ['', '', ''];

        $v0 = $displayTriplet[0] ?? '';
        $v1 = $displayTriplet[1] ?? '';
        $v2 = $displayTriplet[2] ?? '';

        if ($chosenSlots !== null) {
            // overlay plaintext where available; keep encrypted JSON where not
            $v0 = $chosenSlots[0] ?? $v0;
            $v1 = $chosenSlots[1] ?? $v1;
            $v2 = $chosenSlots[2] ?? $v2;
        }

        // IMPORTANT: populate all 3 fields that Twig expects in decodedNote=true mode
        $out->setCustomerText($v0);
        $out->setCustomerTextKMS2($v1);
        $out->setCustomerTextKMS3($v2);

        // This is the only success signal the counter should use
        $out->setDecryptionSucceeded($decryptionSucceeded);

        if ($rateLimitSeconds !== null) {
            $out->setRateLimitSeconds($rateLimitSeconds);
        }

        $out->setCustomerFirstQuestion($input->getCustomerFirstQuestion());
        $out->setCustomerSecondQuestion($input->getCustomerSecondQuestion());
        $out->setBeneficiaryFirstQuestion($input->getBeneficiaryFirstQuestion());
        $out->setBeneficiarySecondQuestion($input->getBeneficiarySecondQuestion());

        return $out;
    }
}