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
        private readonly CryptoService $crypto
    ) {}

    public function __invoke(NoteEditInputDto $input): NoteEditOutputDto
    {
        $out = new NoteEditOutputDto($input->getCustomer());
        $out->setCustomer($input->getCustomer());

        $customerId = $input->getCustomer()->getId();

        $attempts = [
            [
                'answer'   => $input->getCustomerFirstQuestionAnswer(),
                'replicas' => [
                    $input->getCustomerTextAnswerOne(),
                    $input->getCustomerTextAnswerOneKms2(),
                    $input->getCustomerTextAnswerOneKms3(),
                ],
            ],
            [
                'answer'   => $input->getCustomerSecondQuestionAnswer(),
                'replicas' => [
                    $input->getCustomerTextAnswerTwo(),
                    $input->getCustomerTextAnswerTwoKms2(),
                    $input->getCustomerTextAnswerTwoKms3(),
                ],
            ],
            [
                'answer'   => $input->getBeneficiaryFirstQuestionAnswer(),
                'replicas' => [
                    $input->getBeneficiaryTextAnswerOne(),
                    $input->getBeneficiaryTextAnswerOneKms2(),
                    $input->getBeneficiaryTextAnswerOneKms3(),
                ],
            ],
            [
                'answer'   => $input->getBeneficiarySecondQuestionAnswer(),
                'replicas' => [
                    $input->getBeneficiaryTextAnswerTwo(),
                    $input->getBeneficiaryTextAnswerTwoKms2(),
                    $input->getBeneficiaryTextAnswerTwoKms3(),
                ],
            ],
        ];

        $chosenSlots    = null;
        $chosenReplicas = null;
        $fallbackReplicas = null; // for "all failed" case

        foreach ($attempts as $attempt) {
            $answer   = $attempt['answer'];
            $replicas = $attempt['replicas'];

            if (!array_filter($replicas)) {
                continue; // no data for this answer
            }
            if ($answer === null || $answer === '') {
                // remember first non-empty replica set as fallback
                if ($fallbackReplicas === null) {
                    $fallbackReplicas = $replicas;
                }
                continue;
            }

            $slots = $this->crypto->decryptEnvelopeReplicasPerSlot(
                $replicas,
                $customerId,
                $answer
            );

            // did at least one slot decrypt to plaintext?
            $anyPlain = false;
            foreach ($slots as $idx => $val) {
                $orig = $replicas[$idx] ?? null;
                if ($val !== null && $orig !== null && $val !== $orig) {
                    $anyPlain = true;
                    break;
                }
            }

            if ($anyPlain) {
                $chosenReplicas = $replicas;
                $chosenSlots    = $slots;
                break;
            }

            // no plaintext, but keep as potential fallback (encrypted only)
            if ($fallbackReplicas === null) {
                $fallbackReplicas = $replicas;
            }
        }

        // helper to map 3 slots → 3 fields
        $applyTriplet = function (array $base, ?array $overlay) use ($out): void {
            $r0 = $base[0] ?? '';
            $r1 = $base[1] ?? '';
            $r2 = $base[2] ?? '';

            if ($overlay !== null) {
                $s0 = $overlay[0] ?? $r0;
                $s1 = $overlay[1] ?? $r1;
                $s2 = $overlay[2] ?? $r2;
            } else {
                $s0 = $r0;
                $s1 = $r1;
                $s2 = $r2;
            }

            $out->setCustomerText($s0);
            $out->setCustomerTextKMS2($s1);
            $out->setCustomerTextKMS3($s2);
        };

        if ($chosenSlots !== null && $chosenReplicas !== null) {
            // some KMS worked: show plaintext where we have it, encrypted JSON where we don't
            $applyTriplet($chosenReplicas, $chosenSlots);
        } elseif ($fallbackReplicas !== null) {
            // no KMS decrypted anything: show *encrypted* JSON for all three slots
            $applyTriplet($fallbackReplicas, null);
        } else {
            // truly nothing in DB – only here we fall back to empty strings
            $applyTriplet([], null);
        }

        // questions as before
        $out->setCustomerFirstQuestion($input->getCustomerFirstQuestion());
        $out->setCustomerSecondQuestion($input->getCustomerSecondQuestion());
        $out->setBeneficiaryFirstQuestion($input->getBeneficiaryFirstQuestion());
        $out->setBeneficiarySecondQuestion($input->getBeneficiarySecondQuestion());

        return $out;
    }
}
