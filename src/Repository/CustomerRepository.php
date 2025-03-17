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
            ->setParameter('date', new \DateTime('-1 day')) //30 days TODO
            ->getQuery()
            ->getResult();
    }

    public function findPaidAndNotDeletedForCron(int $batchSize, int $offset): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.customerPaymentStatus = :paid')
            ->andWhere('c.deleted_at IS NULL')  // Exclude marked for deletion
            ->setParameter('paid', CustomerPaymentStatusEnum::PAID->value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults($batchSize)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

}
