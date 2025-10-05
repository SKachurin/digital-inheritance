<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Customer;
use App\Entity\Transaction;
use App\Repository\Collection\PageCollection;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method PageCollection    getAll(int $page = 1, int $pageSize = 100, array $criteria = [])
 * @method Transaction            getOneBy(array $criteria, array $orderBy = null)
 * @method null|Transaction       find($id, $lockMode = null, $lockVersion = null)
 * @method null|Transaction       findOneBy(array $criteria, array $orderBy = null)
 * @method Transaction[]         findAll()
 * @method Transaction[]         findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TransactionRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        // @phpstan-ignore-next-line
        parent::__construct($registry, Transaction::class);
    }

    public function findLastPaidByCustomer(Customer $customer): ?Transaction
    {
        return $this->createQueryBuilder('t')
            ->where('t.customer = :customer')
            ->andWhere('t.status = :status')
            ->setParameter('customer', $customer)
            ->setParameter('status', 'paid')
            ->orderBy('t.created_at', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function hasReferralBonus(
        Customer $inviter,
        string $invoiceUuid,
        string $currency
    ): bool {
        $exists = $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->where('t.customer = :inviter')
            ->andWhere('t.plan = :plan')
            ->andWhere('t.status = :status')
            ->andWhere('t.paymentMethod = :pm')
            ->andWhere('t.currency = :cur')
            ->andWhere('t.createdAt >= :since')
            ->setParameter('inviter', $inviter)
            ->setParameter('plan', 'referral')
            ->setParameter('status', 'bonus')
            ->setParameter('pm', $invoiceUuid)
            ->setParameter('cur', $currency)
            ->setParameter('since', new \DateTimeImmutable('-2 days'))
            ->getQuery()
            ->getSingleScalarResult();

        return (int)$exists > 0;
    }

    public function getReferralBalance(Customer $customer): float
    {
        return (float) $this->createQueryBuilder('t')
            ->select('COALESCE(SUM(t.amount), 0)')
            ->where('t.customer = :customer')
            ->andWhere('t.plan = :plan')
            ->andWhere('t.status = :status')
            ->setParameter('customer', $customer)
            ->setParameter('plan', 'referral')
            ->setParameter('status', 'bonus')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
