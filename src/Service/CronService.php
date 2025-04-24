<?php

declare(strict_types=1);

namespace App\Service;

use App\Message\DeleteMarkedAccountsMessage;
use App\Message\MarkExpiredAsNotPaidMessage;
use App\Repository\CustomerRepository;
use App\Queue\CronBatchProducer;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Notifier\Exception\TransportExceptionInterface;

class CronService
{
    public function __construct(
        private CustomerRepository           $customerRepository,
        private CronBatchProducer            $batchProducer,
        private readonly MessageBusInterface $bus,
//        private LoggerInterface              $logger

    )
    {
    }

    /**
     * @throws \Exception|TransportExceptionInterface
     */
    public function executeFiveMinuteTasks(): void
    {
        $batchSize = 30;
        $offset = 0;

        while (true) {
            // Fetch a batch
            $customers = $this->customerRepository->findPaidAndTrialAndNotDeletedForCron(
                $batchSize,
                $offset
            );

//            $this->logger->error('2 CronService == $batchSize: '. $batchSize);

            if (count($customers) === 0) {
                break; // No more customers
            }

            //Extract IDs
            $customerIds = array_map(fn($c) => $c->getId(), $customers);

            //Dispatch them
            $this->batchProducer->produce($customerIds);

            //Increase offset
            $offset += $batchSize;
        }

        $now = new \DateTimeImmutable();
        $hour = (int)$now->format('H');
        $minute = (int)$now->format('i');

        // Run deleteMarkedAccounts once between 00:00 and 00:15
        if ($hour === 0 && $minute <= 15) {
            $this->bus->dispatch(new DeleteMarkedAccountsMessage());
        }

        // Run once between 02:00–02:10
        if ($hour === 2 && $minute <= 15) {
            $this->bus->dispatch(new MarkExpiredAsNotPaidMessage());
//            $this->logger->error('CronBatchProducer dispatching CronBatchMessage');
        }

    }

}

