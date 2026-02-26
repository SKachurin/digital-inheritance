<?php

namespace App\CommandHandler\Note\Decrypt;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsMessageHandler]
class BeneficiaryNoteDecryptCounterHandler
{
    private int $MAX_ATTEMPTS = 5;
    private int $FIRST_LOCK_TIME = 60; // minutes

    public function __construct(
        private LoggerInterface $logger,
        private EntityManagerInterface $entityManager,
        private TranslatorInterface $translator
    ) {}

    /**
     * @throws \Exception
     */
    public function __invoke(BeneficiaryNoteDecryptOutputDto $inputDto): BeneficiaryNoteDecryptOutputDto
    {
        $note = $inputDto->getNote();

        $now = new \DateTimeImmutable();

        // NEW: handle KMS 429
        $rateLimitSeconds = $inputDto->getRateLimitSeconds();
        if ($rateLimitSeconds !== null) {

            $lockoutUntil = $now->add(new \DateInterval('PT' . $rateLimitSeconds . 'S'));
            $note->setLockoutUntil($lockoutUntil);

            $minutes = (int) ceil($rateLimitSeconds / 60);
            $beneficiaryCongrats = $this->translator->trans('errors.note.decrypt.kms_rate_limited', [
                '%minutes%' => $minutes,
            ]);

            $this->entityManager->persist($note);
            $this->entityManager->flush();

            $inputDto->setBeneficiaryCongrats($beneficiaryCongrats);
            $inputDto->setAttemptCount($note->getAttemptCount());
            $inputDto->setLockoutUntil($note->getLockoutUntil());

            return $inputDto;
        }

        // OLD logic below – unchanged
        $attemptCount  = $note->getAttemptCount() ?? 0;
        $lockoutUntil  = $note->getLockoutUntil();

        if ($lockoutUntil && $now < $lockoutUntil) {

            $minutesLeft = (int) ceil(($lockoutUntil->getTimestamp() - $now->getTimestamp()) / 60);

            $beneficiaryCongrats = $this->translator->trans('errors.note.decrypt.locked_out', [
                '%minutes%' => $minutesLeft,
            ]);

        } else {

//            if ($inputDto->getCustomerText() == null) {
            if (!$inputDto->isDecryptionSucceeded()) {

                $attemptCount++;
                $note->setAttemptCount($attemptCount);

                if ($attemptCount < $this->MAX_ATTEMPTS) {

                    $beneficiaryCongrats = $this->translator->trans('errors.note.decrypt.wrong_answer_attempts', [
                        '%used%' => $attemptCount,
                        '%max%'  => $this->MAX_ATTEMPTS,
                    ]);
                } elseif ($attemptCount == $this->MAX_ATTEMPTS) {

                    $beneficiaryCongrats = $this->translator->trans('errors.note.decrypt.wrong_answer_locked', [
                        '%minutes%' => $this->FIRST_LOCK_TIME,
                    ]);
                    $lockoutUntil = $now->add(new \DateInterval('PT' . $this->FIRST_LOCK_TIME . 'M'));
                    $note->setLockoutUntil($lockoutUntil);

                } else {

                    $lockTime = ($attemptCount - $this->MAX_ATTEMPTS + 1) * $this->FIRST_LOCK_TIME;
                    $beneficiaryCongrats = $this->translator->trans('errors.note.decrypt.too_many_attempts', [
                        '%minutes%' => $lockTime,
                    ]);
                    $lockoutUntil = $now->add(new \DateInterval('PT' . $lockTime . 'M'));
                    $note->setLockoutUntil($lockoutUntil);
                }
            } else {
                // SUCCESS – reset attempts
                $note->setAttemptCount(0);
                $note->setLockoutUntil(null);
                $beneficiaryCongrats = $this->translator->trans('errors.note.decrypt.success');
            }
        }

        $this->entityManager->persist($note);
        $this->entityManager->flush();

        $inputDto->setBeneficiaryCongrats($beneficiaryCongrats);
        $inputDto->setAttemptCount($note->getAttemptCount());
        $inputDto->setLockoutUntil($note->getLockoutUntil());

        return $inputDto;
    }
}