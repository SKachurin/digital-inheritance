<?php

declare(strict_types=1);

namespace App\Service;

use App\Enum\CustomerPaymentStatusEnum;
use App\Repository\CustomerRepository;
use App\Queue\CronBatchProducer;
use Psr\Log\LoggerInterface;
use Symfony\Component\Notifier\Exception\TransportExceptionInterface;

class CronService
{
    private LoggerInterface $logger;

    public function __construct(
        private CustomerRepository $customerRepository,
        private CronBatchProducer $batchProducer,
//        LoggerInterface $logger

    ) {
//        $this->logger = $logger;
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
            $customers = $this->customerRepository->findBy(
                ['customerPaymentStatus' => CustomerPaymentStatusEnum::PAID->value],
                ['id' => 'ASC'],
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

//        $this->logger->info('All customers have been queued for pipeline processing.');

    }

}

