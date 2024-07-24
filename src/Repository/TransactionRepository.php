<?php

declare(strict_types=1);

namespace App\Repository;

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
}
