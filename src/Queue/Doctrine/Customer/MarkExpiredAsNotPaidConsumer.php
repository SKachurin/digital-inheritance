<?php


namespace App\Queue\Doctrine\Customer;

use App\Enum\CustomerPaymentStatusEnum;
use App\Message\MarkExpiredAsNotPaidMessage;
use App\Repository\CustomerRepository;
use App\Repository\TransactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class MarkExpiredAsNotPaidConsumer
{
    public function __construct(
        private readonly CustomerRepository     $customerRepository,
        private readonly TransactionRepository  $transactionRepository,
        private readonly EntityManagerInterface $em
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
                break;
            }

            foreach ($paidCustomers as $paidCustomer) {
                $lastTransaction = $this->transactionRepository->findLastPaidByCustomer($paidCustomer);

                if (!$lastTransaction) {
                    $paidCustomer->setCustomerPaymentStatus(CustomerPaymentStatusEnum::NOT_PAID);
                    $this->em->persist($paidCustomer);
                    continue;
                }

                $plan = $lastTransaction->getPlan();
                $amount = $lastTransaction->getAmount();

                $pricePerMonth = match ($plan) {
                    'standard' => 5,
                    'premium' => 25,
                    default => null,
                };

                if (!$pricePerMonth || !$amount) {
                    $paidUntil = null;
                    //TODO huge problem - message the admin
                } else {
                    $monthsPaid = (int) floor($amount / $pricePerMonth);
                    $daysPaid = (int) floor($monthsPaid * 31);
                    $paidUntil = (clone $lastTransaction->getCreatedAt())->modify(sprintf('+%d days', $daysPaid));

                }

                if ($paidUntil && $paidUntil < $now) {
                    $paidCustomer->setCustomerPaymentStatus(CustomerPaymentStatusEnum::NOT_PAID);
                    $this->em->persist($paidCustomer);
                }
            }

            $this->em->flush();
            $this->em->clear(); // Free memory
            $offset += $batchSize;
        }
    }
}