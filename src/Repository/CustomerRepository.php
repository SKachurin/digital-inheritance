<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Contact;
use App\Entity\Customer;
use App\Enum\CustomerPaymentStatusEnum;
use App\Repository\Collection\PageCollection;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method PageCollection       getAll(int $page = 1, int $pageSize = 100, array $criteria = [])
 * @method Customer            getOneBy(array $criteria, array $orderBy = null)
 * @method null|Customer       find($id, $lockMode = null, $lockVersion = null)
 * @method null|Customer       findOneBy(array $criteria, array $orderBy = null)
 * @method Customer[]         findAll()
 * @method Customer[]         findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CustomerRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        // @phpstan-ignore-next-line
        parent::__construct($registry, Customer::class);
    }

    /**
     * @return array{Customer, Contact[]}|null
     */
    public function findLastWithContacts(): ?array
    {
        $customer = $this->createQueryBuilder('c')
            ->leftJoin('c.contacts', 'contact')
            ->addSelect('contact')
            ->orderBy('c.id', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getOneOrNullResult();

        if ($customer instanceof Customer) {
            $contacts = $customer->getContacts()->toArray();

            foreach ($contacts as $contact) {
                if (!$contact instanceof Contact) {
                    throw new \UnexpectedValueException('Expected instance of Contact');
                }
            }
            return [$customer, $contacts];
        }

        return null;
    }

    public function findAllMarkedForDeletion(): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.deleted_at IS NOT NULL')
            ->andWhere('c.deleted_at <= :date')
            ->setParameter('date', new \DateTime('-30 days'))
            ->getQuery()
            ->getResult();
    }

    public function findPaidAndTrialAndNotDeletedForCron(int $batchSize, int $offset): array
    {
        return $this->createQueryBuilder('c')
//            ->where('c.customerPaymentStatus = :paid')
//            ->andWhere('c.deleted_at IS NULL')  // Exclude marked for deletion
//            ->setParameter('paid', CustomerPaymentStatusEnum::PAID->value)
//            ->orderBy('c.id', 'ASC')
//            ->setMaxResults($batchSize)
//            ->setFirstResult($offset)
//            ->getQuery()
//            ->getResult();
            ->where('(c.customerPaymentStatus = :paid OR (c.customerPaymentStatus = :notPaid AND c.created_at >= :trialStart))')
            ->andWhere('c.deleted_at IS NULL')
            ->setParameter('paid', CustomerPaymentStatusEnum::PAID->value)
            ->setParameter('notPaid', CustomerPaymentStatusEnum::NOT_PAID->value)
            ->setParameter('trialStart', new \DateTimeImmutable('-3 days'))
            ->orderBy('c.id', 'ASC')
            ->setMaxResults($batchSize)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param array $customerIds
     * @return Customer[]
     */
    public function findActiveOrTrialByIds(array $customerIds): array
    {
        $qb = $this->createQueryBuilder('c');

        $qb->where($qb->expr()->in('c.id', ':ids'))
            ->andWhere(
                $qb->expr()->orX(
                    'c.customerPaymentStatus = :paid',
                    'c.created_at >= :trialStart'
                )
            )
            ->setParameter('ids', $customerIds)
            ->setParameter('paid', CustomerPaymentStatusEnum::PAID->value)
            ->setParameter('trialStart', (new \DateTimeImmutable())->modify('-3 days'));

        return $qb->getQuery()->getResult();
    }

    public function findPaidWithPagination(int $limit, int $offset): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.customerPaymentStatus = :paid')
            ->setParameter('paid', CustomerPaymentStatusEnum::PAID->value)
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param Customer $customer
     * @return int
     */
    public function countReferrals(Customer $customer): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.invitedBy = :customer')
            ->setParameter('customer', $customer)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @param Customer $customer
     * @return int
     */
    public function countActiveReferrals(Customer $customer): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.invitedBy = :customer')
            ->andWhere('c.customerPaymentStatus = :status')
            ->setParameter('customer', $customer)
            ->setParameter('status', CustomerPaymentStatusEnum::PAID->value)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @param Customer $customer
     * @return string | null
     */
    public function getReferralCode(Customer $customer): ?string
    {
        return $this->createQueryBuilder('c')
            ->select('c.referralCode')
            ->where('c.id = :customer')
            ->setParameter('customer', $customer->getId())
            ->getQuery()
            ->getOneOrNullResult()['referralCode'] ?? null;
    }
}
