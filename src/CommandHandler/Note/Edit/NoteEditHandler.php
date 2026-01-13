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

        $chosenTriplet   = null; // encrypted json triplet we display against
        $chosenSlots     = null; // plaintext overlay [0..2] (nulls where not decrypted)
        $fallbackTriplet = null; // last-seen triplet that exists in DB (encrypted-only fallback)

        foreach ($attempts as $attempt) {
            $answer  = $attempt['answer'];
            $triplet = $attempt['triplet'];

            // skip if triplet has no DB data
            if (!array_filter($triplet)) {
                continue;
            }

            // always keep “current triplet” as fallback candidate
            $fallbackTriplet = $triplet;

            // no answer => cannot decrypt this triplet, but it's still a valid fallback
            if ($answer === null || $answer === '') {
                continue;
            }

            try {
                // returns [0=>?plain, 1=>?plain, 2=>?plain]
                $slots = $this->crypto->decryptEnvelopeTripletForUi($triplet, $customerId, $answer);
            } catch (KmsRateLimitedExceptionService $e) {
                $rateLimitSeconds = $e->getRetryAfterSeconds();
                // stop trying other answers – API already told us to wait
                break;
            }

            $anyPlain = ($slots[0] !== null) || ($slots[1] !== null) || ($slots[2] !== null);

            if ($anyPlain) {
                $chosenTriplet = $triplet;
                $chosenSlots   = $slots;
                break;
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