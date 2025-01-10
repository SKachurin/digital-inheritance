<?php

declare(strict_types=1);

namespace App\Service;

use App\Enum\CustomerPaymentStatusEnum;
use App\Repository\CustomerRepository;
use App\Queue\CronBatchProducer;
use Symfony\Component\Notifier\Exception\TransportExceptionInterface;

class CronService
{
    public function __construct(
        private CustomerRepository $customerRepository,
        private CronBatchProducer $batchProducer,
//        private LoggerInterface $logger
    ) {}

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
                ['paymentStatus' => CustomerPaymentStatusEnum::PAID->value],
                ['id' => 'ASC'],
                $batchSize,
                $offset
            );

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

