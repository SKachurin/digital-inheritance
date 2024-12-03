<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class CronService
{
    private $entityManager;
    private LoggerInterface $logger;

    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    public function executeFiveMinuteTasks(): void
    {
        $this->logger->info('Starting five-minute cron tasks.');

        // Check in the database if it's time to perform operations
        // Example logic:
        $tasks = $this->entityManager->getRepository(Task::class)->findPendingTasks();

        foreach ($tasks as $task) {
            // Perform your operations
            // ...

            // Update task status
            $task->setStatus('completed');
        }

        // Save changes to the database
        $this->entityManager->flush();

        $this->logger->info('Five-minute cron tasks completed successfully.');
    }
}

