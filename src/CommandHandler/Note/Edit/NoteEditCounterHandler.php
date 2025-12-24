<?php

namespace App\CommandHandler\Note\Edit;

use App\Entity\Note;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class NoteEditCounterHandler
{
    private int $MAX_ATTEMPTS = 5;
    private int $FIRST_LOCK_TIME = 60; // minutes

    public function __construct(
        private LoggerInterface $logger,
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
            $customerCongrats = sprintf(
                'Too many decryption requests for this answer. Next attempt will be available in %d minutes.',
                $minutes
            );

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
            $interval    = $now->diff($lockoutUntil);
            $minutesLeft = $interval->i + ($interval->h * 60);

            $customerCongrats = 'You used all attempts. Next attempt will be available in ' . $minutesLeft . ' minutes.';
        } else {
            if ($input->getCustomerText() == null) {
                $attemptCount++;
                $note->setAttemptCount($attemptCount);

                if ($attemptCount < $this->MAX_ATTEMPTS) {
                    $customerCongrats = 'Wrong password. Used ' . $attemptCount . ' of ' . $this->MAX_ATTEMPTS . ' attempts.';
                } elseif ($attemptCount == $this->MAX_ATTEMPTS) {
                    $customerCongrats = 'Wrong password. Used all attempts. Next attempt will be available after ' . $this->FIRST_LOCK_TIME . ' minutes.';
                    $note->setLockoutUntil($now->add(new \DateInterval('PT' . $this->FIRST_LOCK_TIME . 'M')));
                } else {
                    $lockTime = ($attemptCount - $this->MAX_ATTEMPTS + 1) * $this->FIRST_LOCK_TIME;
                    $customerCongrats = 'Wrong password. Used more than ' . $this->MAX_ATTEMPTS . ' attempts. Next attempt in ' . $lockTime . ' minutes.';
                    $note->setLockoutUntil($now->add(new \DateInterval('PT' . $lockTime . 'M')));
                }
            } else {
                $note->setAttemptCount(0);
                $note->setLockoutUntil(null);
                $customerCongrats = 'If you can read your text, you did everything right!';
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