<?php


namespace App\Queue\Doctrine\Customer;

use App\Enum\CustomerPaymentStatusEnum;
use App\Message\MarkExpiredAsNotPaidMessage;
use App\Repository\CustomerRepository;
use App\Repository\TransactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class MarkExpiredAsNotPaidConsumer
{
    public function __construct(
        private readonly CustomerRepository     $customerRepository,
        private readonly TransactionRepository  $transactionRepository,
        private readonly EntityManagerInterface $em,
//        private LoggerInterface                 $logger
    )
    {
    }

    public function __invoke(MarkExpiredAsNotPaidMessage $message): void
    {
        $batchSize = 50;
        $offset = 0;
        $now = new \DateTimeImmutable();

        while (true) {
            $paidCustomers = $this->customerRepository->findPaidWithPagination($batchSize, $offset);

            if (count($paidCustomers) === 0) {
//                $this->logger->error('No more paid customers found in batch.');
                break;
            }

            foreach ($paidCustomers as $paidCustomer) {
                $lastTransaction = $this->transactionRepository->findLastPaidByCustomer($paidCustomer);

                if (!$lastTransaction) {
                    $paidCustomer->setCustomerPaymentStatus(CustomerPaymentStatusEnum::NOT_PAID);
                    $this->em->persist($paidCustomer);
//                    $this->logger->error('Marked customer ID ' . $paidCustomer->getId() . ' as NOT_PAID — no transaction found.');
                    continue;
                }

//                $plan = $lastTransaction->getPlan();
//                $amount = $lastTransaction->getAmount();
//
//                $pricePerMonth = $this->planPriceResolver->getPricePerMonth($plan);

                $paidUntil = $lastTransaction->getPaidUntil();

                if (!$paidUntil) {

                    return;
                    //TODO huge problem - message the admin
                }

                if ($paidUntil < $now) {
                    $paidCustomer->setCustomerPaymentStatus(CustomerPaymentStatusEnum::NOT_PAID);
                    $this->em->persist($paidCustomer);
//                    $this->logger->error('Marked customer ID ' . $paidCustomer->getId() . ' as NOT_PAID — expired.');
                }
            }

            $this->em->flush();
            $this->em->clear(); // Free memory
            $offset += $batchSize;
        }
    }
}