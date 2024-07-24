<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Beneficiary;
use App\Repository\Collection\PageCollection;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method PageCollection         getAll(int $page = 1, int $pageSize = 100, array $criteria = [])
 * @method Beneficiary            getOneBy(array $criteria, array $orderBy = null)
 * @method null|Beneficiary       find($id, $lockMode = null, $lockVersion = null)
 * @method null|Beneficiary       findOneBy(array $criteria, array $orderBy = null)
 * @method Beneficiary[]         findAll()
 * @method Beneficiary[]         findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BeneficiaryRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        // @phpstan-ignore-next-line
        parent::__construct($registry, Beneficiary::class);
    }
}
