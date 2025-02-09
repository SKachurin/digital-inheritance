<?php
namespace App\CommandHandler\Note\Decrypt;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class BeneficiaryNoteDecryptCounterHandler
{
    private int $MAX_ATTEMPTS = 5;
    private int $FIRST_LOCK_TIME = 60; // minutes

    public function __construct(
        private LoggerInterface $logger,
        private EntityManagerInterface $entityManager
    ) {}

    /**
     * @throws \Exception
     */
    public function __invoke(BeneficiaryNoteDecryptOutputDto $inputDto): BeneficiaryNoteDecryptOutputDto
    {
        $note = $inputDto->getNote();

        $attemptCount = $note->getAttemptCount() ?? 0;
        $now = new \DateTimeImmutable();
        $lockoutUntil = $note->getLockoutUntil();

        if ($lockoutUntil && $now < $lockoutUntil) {

            $interval = $now->diff($lockoutUntil);
            $minutesLeft = $interval->i + ($interval->h * 60);
            $beneficiaryCongrats = sprintf(
                'You used all attempts. Next attempt available in %d minutes.',
                $minutesLeft
            );
        } else {

            if ($inputDto->getCustomerText() == null) {

                $attemptCount++;
                $note->setAttemptCount($attemptCount);

                if ($attemptCount < $this->MAX_ATTEMPTS) {

                    $beneficiaryCongrats = sprintf(
                        'Wrong password. Used %d of %d attempts.',
                        $attemptCount,
                        $this->MAX_ATTEMPTS
                    );
                } elseif ($attemptCount == $this->MAX_ATTEMPTS) {

                    $beneficiaryCongrats = sprintf(
                        'Wrong password. Used all attempts. Next attempt available after %d minutes.',
                        $this->FIRST_LOCK_TIME
                    );
                    $lockoutUntil = $now->add(new \DateInterval('PT' . $this->FIRST_LOCK_TIME . 'M'));
                    $note->setLockoutUntil($lockoutUntil);

                } else {

                    // More than MAX_ATTEMPTS
                    $lockTime = ($attemptCount - $this->MAX_ATTEMPTS + 1) * $this->FIRST_LOCK_TIME;
                    $beneficiaryCongrats = sprintf(
                        'Too many attempts. Next attempt in %d minutes.',
                        $lockTime
                    );
                    $lockoutUntil = $now->add(new \DateInterval('PT' . $lockTime . 'M'));
                    $note->setLockoutUntil($lockoutUntil);
                }
            } else {
                // SUCCESS – reset attempts
                $note->setAttemptCount(0);
                $note->setLockoutUntil(null);
                $beneficiaryCongrats = 'If you can read your text, you did everything right!';
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
