<?php

declare(strict_types=1);

namespace App\Queue\Doctrine\Customer;

use App\Entity\Customer;
use App\Entity\Transaction;
use App\Message\ReferralBonusMessage;
use App\Repository\CustomerRepository;
use App\Repository\TransactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ReferralBonusConsumer
{
    public function __construct(
        private readonly CustomerRepository $customerRepository,
        private readonly EntityManagerInterface $em,
        private readonly TransactionRepository $transactionRepository,
    ) {}

    public function __invoke(ReferralBonusMessage $message): void
    {
        $inviter = $this->customerRepository->find($message->inviterId);
        if (!$inviter instanceof Customer) {
            return;
        }

        //  has a bonus for this invoice already been created?
        if ($this->transactionRepository->hasReferralBonus($inviter, $message->invoiceUuid, $message->currency)) {
            return; // already recorded
        }

        $tx = new Transaction($inviter);
        $tx->setAmount($message->amount)
            ->setPlan('referral')
            ->setStatus('bonus')
            ->setPaymentMethod('referral')
            ->setCurrency($message->currency);

        $this->em->persist($tx);
        $this->em->flush();
    }
}
