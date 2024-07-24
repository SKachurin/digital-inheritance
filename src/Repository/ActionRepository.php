<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Action;
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
}
