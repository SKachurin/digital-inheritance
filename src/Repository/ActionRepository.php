<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Action;
use App\Entity\Customer;
use App\Enum\ActionTypeEnum;
use App\Repository\Collection\PageCollection;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method PageCollection    getAll(int $page = 1, int $pageSize = 100, array $criteria = [])
 * @method Action            getOneBy(array $criteria, array $orderBy = null)
 * @method null|Action       find($id, $lockMode = null, $lockVersion = null)
 * @method null|Action       findOneBy(array $criteria, array $orderBy = null)
 * @method Action[]         findAll()
 * @method Action[]         findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ActionRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        // @phpstan-ignore-next-line
        parent::__construct($registry, Action::class);
    }

    public function customerVerifiedSecondEmail(Customer $customer): bool
    {
        $firstEmail = $this->createQueryBuilder('n')
            ->select('1') // Only checking if the record exists
            ->where('n.customer = :customer')
            ->andWhere('n.actionType = :actionType')
            ->setParameter('customer', $customer->getId())
            ->setParameter('actionType', ActionTypeEnum::EMAIL_SEND)
            ->getQuery()
            ->getOneOrNullResult();

        return $firstEmail !== null;
    }

    public function customerHasActions(Customer $customer): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.customer = :customer')
            ->setParameter('customer', $customer)
            ->getQuery()
            ->getResult();
    }
}
