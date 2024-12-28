<?php

declare(strict_types=1);

namespace App\Service;

use App\Enum\CustomerPaymentStatusEnum;
use App\Repository\CustomerRepository;
use App\Queue\CronBatchProducer;
//use Doctrine\ORM\EntityManagerInterface;
//use Psr\Log\LoggerInterface;
use Symfony\Component\Notifier\Exception\TransportExceptionInterface;

class CronService
{
    public function __construct(
//        private EntityManagerInterface $entityManager,
        private CustomerRepository $customerRepository,
        private CronBatchProducer $batchProducer,
//        private LoggerInterface $logger
    ) {}

    /**
     * @throws \Exception|TransportExceptionInterface
     */
    public function executeFiveMinuteTasks(): void
    {
//        $paidCustomers = $this->customerRepository->findBy(['paymentStatus' => CustomerPaymentStatusEnum::PAID->value]);
//
//        foreach ($paidCustomers as $customer) {
//
//            /** @var Pipeline|null $pipeline */
//            $pipeline = $this->pipelineRepository->findOneBy(['customer' => $customer]); //->getId()
//
//            if ($pipeline->getPipelineStatus() === ActionStatusEnum::ACTIVATED->value) {
//                $this->processPipeline($pipeline);
//
//                $this->entityManager->persist($pipeline);
//                $this->entityManager->flush();
//            }
//        }
//
//        // flush once after all updates - If I have 20 thousand Customers I'll still Flush in the end !!??
////        $this->entityManager->flush();
//
//
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

