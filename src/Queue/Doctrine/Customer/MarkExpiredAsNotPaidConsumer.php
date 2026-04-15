<?php

namespace App\Queue\Doctrine\Customer;

use App\Enum\CustomerPaymentStatusEnum;
use App\Message\MarkExpiredAsNotPaidMessage;
use App\Message\PaymentExpirationReminderMessage;
use App\Repository\CustomerRepository;
use App\Repository\TransactionRepository;
use App\Service\AdminAlertService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
class MarkExpiredAsNotPaidConsumer
{
    public function __construct(
        private readonly CustomerRepository $customerRepository,
        private readonly TransactionRepository $transactionRepository,
        private readonly EntityManagerInterface $em,
        private readonly MessageBusInterface $bus,
        private readonly AdminAlertService $adminAlertService,
    ) {
    }

    public function __invoke(MarkExpiredAsNotPaidMessage $message): void
    {
        $batchSize = 50;
        $offset = 0;
        $now = new \DateTimeImmutable('now');

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

                $paidUntil = $lastTransaction->getPaidUntil();

                if (!$paidUntil) {
                    $this->adminAlertService->notifyMissingPaidUntil(
                        $paidCustomer->getId(),
                        $lastTransaction->getId()
                    );
                    continue;
                }

                $daysLeft = $this->calculateWholeDaysLeft($now, $paidUntil);

                if (in_array($daysLeft, [3, 2, 1], true)) {
                    $this->bus->dispatch(
                        new PaymentExpirationReminderMessage(
                            $paidCustomer->getId(),
                            $daysLeft
                        )
                    );
                }

                if ($paidUntil < $now) {
                    $paidCustomer->setCustomerPaymentStatus(CustomerPaymentStatusEnum::NOT_PAID);
                    $this->em->persist($paidCustomer);
                }
            }

            $this->em->flush();
            $this->em->clear();
            $offset += $batchSize;
        }
    }

    private function calculateWholeDaysLeft(\DateTimeImmutable $now, \DateTimeImmutable $paidUntil): int
    {
        $today = $now->setTime(0, 0);
        $paidUntilDay = $paidUntil->setTime(0, 0);

        return (int) $today->diff($paidUntilDay)->format('%r%a');
    }
}