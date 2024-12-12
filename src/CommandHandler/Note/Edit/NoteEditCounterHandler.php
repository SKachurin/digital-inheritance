<?php

namespace App\CommandHandler\Note\Edit;

use App\Entity\Note;
use App\Service\CryptoService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class NoteEditCounterHandler
{
//    private ParameterBagInterface $params;
    private LoggerInterface $logger;
    private EntityManagerInterface $entityManager;
    private int $MAX_ATTEMPTS = 5;
    private int $FIRST_LOCK_TIME = 60;

    public function __construct(
//        ParameterBagInterface $params,
        LoggerInterface $logger,
        EntityManagerInterface $entityManager
    ) {
//        $this->params = $params;
        $this->logger = $logger;
        $this->entityManager = $entityManager;
    }

    /**
     * @throws Exception
     */
    public function __invoke(NoteEditOutputDto $input): NoteEditOutputDto
    {
        $note = $this->entityManager->getRepository(Note::class)->findOneBy(['customer' => $input->getCustomer()]); //->getId()

        if (!$note instanceof Note) {
            throw new \UnexpectedValueException('Note not found.');
        }

        $attemptCount = $note->getAttemptCount() ?? 0;
        $now = new \DateTimeImmutable();
        $lockoutUntil = $note->getLockoutUntil();

        // If locked
        if ($lockoutUntil && $now < $lockoutUntil) {

            $interval = $now->diff($lockoutUntil);
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
                    $lockoutUntil = $now->add(new \DateInterval('PT' . $this->FIRST_LOCK_TIME . 'M'));
                    $note->setLockoutUntil($lockoutUntil);

                } else {

                    $lockTime = ($attemptCount - $this->MAX_ATTEMPTS + 1) * $this->FIRST_LOCK_TIME;
                    $customerCongrats = 'Wrong password. Used more than ' . $this->MAX_ATTEMPTS . ' attempts. Next attempt in ' . $lockTime . ' minutes.';
                    $lockoutUntil = $now->add(new \DateInterval('PT' . $lockTime . 'M'));
                    $note->setLockoutUntil($lockoutUntil);
                }
            } else {

                // Successful decryption, reset attempts
                $note->setAttemptCount(0);
                $note->setLockoutUntil(null);
                $customerCongrats = 'If you can read your text, you did everything right!';
            }
        }

        // Persist changes to DB
        $this->entityManager->persist($note);
        $this->entityManager->flush();

        $input->setCustomerCongrats($customerCongrats);
        $input->setAttemptCount($note->getAttemptCount());
        $input->setLockoutUntil($note->getLockoutUntil());

        return $input;
    }
}
