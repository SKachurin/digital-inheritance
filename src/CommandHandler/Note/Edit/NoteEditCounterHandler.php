<?php

namespace App\CommandHandler\Note\Edit;

use App\Entity\Note;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsMessageHandler]
class NoteEditCounterHandler
{
    private int $MAX_ATTEMPTS = 5;
    private int $FIRST_LOCK_TIME = 60; // minutes

    public function __construct(
        private LoggerInterface $logger,
        private TranslatorInterface $translator,
        private EntityManagerInterface $entityManager
    ) {}

    /**
     * @throws Exception
     */
    public function __invoke(NoteEditOutputDto $input): NoteEditOutputDto
    {
        /** @var Note|null $note */
        $note = $this->entityManager->getRepository(Note::class)
            ->findOneBy(['customer' => $input->getCustomer()]);

        if (!$note instanceof Note) {
            throw new \UnexpectedValueException('Note not found.');
        }

        $now = new \DateTimeImmutable();

        // Handle KMS rate limit (HTTP 429 translated to RateLimitSeconds)
        $rateLimitSeconds = $input->getRateLimitSeconds();
        if ($rateLimitSeconds !== null) {
            $lockoutUntil = $now->add(new \DateInterval('PT' . $rateLimitSeconds . 'S'));
            $note->setLockoutUntil($lockoutUntil);

            $minutes = (int) ceil($rateLimitSeconds / 60);
            $customerCongrats = $this->translator->trans('errors.note.decrypt.kms_rate_limited', [
                '%minutes%' => $minutes,
            ]);

            $this->entityManager->persist($note);
            $this->entityManager->flush();

            $input->setCustomerCongrats($customerCongrats);
            $input->setAttemptCount($note->getAttemptCount());
            $input->setLockoutUntil($note->getLockoutUntil());

            return $input;
        }

        // Normal attempt-based lockout logic (unchanged)
        $attemptCount = $note->getAttemptCount() ?? 0;
        $lockoutUntil = $note->getLockoutUntil();

        if ($lockoutUntil && $now < $lockoutUntil) {
            $minutesLeft = (int) ceil(($lockoutUntil->getTimestamp() - $now->getTimestamp()) / 60);

            $customerCongrats = $this->translator->trans('errors.note.decrypt.locked_out', [
                '%minutes%' => $minutesLeft,
            ]);
        } else {
//            if ($input->getCustomerText() == null) {
            if (!$input->isDecryptionSucceeded()) {
                $attemptCount++;
                $note->setAttemptCount($attemptCount);

                if ($attemptCount < $this->MAX_ATTEMPTS) {
                    $customerCongrats = $this->translator->trans('errors.note.decrypt.wrong_password_attempts', [
                        '%used%' => $attemptCount,
                        '%max%'  => $this->MAX_ATTEMPTS,
                    ]);
                } elseif ($attemptCount == $this->MAX_ATTEMPTS) {
                    $customerCongrats = $this->translator->trans('errors.note.decrypt.wrong_password_locked', [
                        '%minutes%' => $this->FIRST_LOCK_TIME,
                    ]);
                    $note->setLockoutUntil($now->add(new \DateInterval('PT' . $this->FIRST_LOCK_TIME . 'M')));
                } else {
                    $lockTime = ($attemptCount - $this->MAX_ATTEMPTS + 1) * $this->FIRST_LOCK_TIME;
                    $customerCongrats = $this->translator->trans('errors.note.decrypt.too_many_attempts', [
                        '%minutes%' => $lockTime,
                    ]);
                    $note->setLockoutUntil($now->add(new \DateInterval('PT' . $lockTime . 'M')));
                }
            } else {
                $note->setAttemptCount(0);
                $note->setLockoutUntil(null);
                $customerCongrats = $this->translator->trans('errors.note.decrypt.success');
            }
        }

        $this->entityManager->persist($note);
        $this->entityManager->flush();

        $input->setCustomerCongrats($customerCongrats);
        $input->setAttemptCount($note->getAttemptCount());
        $input->setLockoutUntil($note->getLockoutUntil());

        return $input;
    }
}