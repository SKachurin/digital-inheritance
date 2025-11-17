<?php
declare(strict_types=1);

namespace App\CommandHandler\Note\Edit;

use App\Service\CryptoService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class NoteEditHandler
{
    public function __construct(private readonly CryptoService $crypto)
    {
    }

    public function __invoke(NoteEditInputDto $input): NoteEditOutputDto
    {
        $out = new NoteEditOutputDto($input->getCustomer());
        $out->setCustomer($input->getCustomer());

        $customerId = $input->getCustomer()->getId();
        $decrypted = null;

        // Try in this order: a1 → a2 → b1 → b2 (skip empty answers automatically)
        $try = function (?string $answer, array $replicas) use ($customerId): ?string {
            if (!$answer) {
                return null; // skip if user didn’t provide this cryptex
            }
            $plain = $this->crypto->decryptEnvelopeReplicas($replicas, $customerId, $answer);
            return $plain === false ? null : $plain;
        };

        // Build replica sets exactly as stored by the create flow
        if ($decrypted === null) {
            $decrypted = $try(
                $input->getCustomerFirstQuestionAnswer(),
                [
                    $input->getCustomerTextAnswerOne(),
                    $input->getCustomerTextAnswerOneKms2(),
                    $input->getCustomerTextAnswerOneKms3(),
                ]
            );
        }

        if ($decrypted === null) {
            $decrypted = $try(
                $input->getCustomerSecondQuestionAnswer(),
                [
                    $input->getCustomerTextAnswerTwo(),
                    $input->getCustomerTextAnswerTwoKms2(),
                    $input->getCustomerTextAnswerTwoKms3(),
                ]
            );
        }

        if ($decrypted === null) {
            $decrypted = $try(
                $input->getBeneficiaryFirstQuestionAnswer(),
                [
                    $input->getBeneficiaryTextAnswerOne(),
                    $input->getBeneficiaryTextAnswerOneKms2(),
                    $input->getBeneficiaryTextAnswerOneKms3(),
                ]
            );
        }

        if ($decrypted === null) {
            $decrypted = $try(
                $input->getBeneficiarySecondQuestionAnswer(),
                [
                    $input->getBeneficiaryTextAnswerTwo(),
                    $input->getBeneficiaryTextAnswerTwoKms2(),
                    $input->getBeneficiaryTextAnswerTwoKms3(),
                ]
            );
        }

        // If none succeeded, keep it null — your counter/lockout flow will handle it
        $out->setCustomerText($decrypted);

        // Pass through questions (already decrypted server-side in the controller)
        $out->setCustomerFirstQuestion($input->getCustomerFirstQuestion());
        $out->setCustomerSecondQuestion($input->getCustomerSecondQuestion());
        $out->setBeneficiaryFirstQuestion($input->getBeneficiaryFirstQuestion());
        $out->setBeneficiarySecondQuestion($input->getBeneficiarySecondQuestion());

        return $out;
    }
}
