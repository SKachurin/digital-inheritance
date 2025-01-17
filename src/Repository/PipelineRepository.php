<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Customer;
use App\Entity\Pipeline;
use App\Repository\Collection\PageCollection;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method PageCollection    getAll(int $page = 1, int $pageSize = 100, array $criteria = [])
 * @method Pipeline            getOneBy(array $criteria, array $orderBy = null)
 * @method null|Pipeline       find($id, $lockMode = null, $lockVersion = null)
 * @method null|Pipeline       findOneBy(array $criteria, array $orderBy = null)
 * @method Pipeline[]         findAll()
 * @method Pipeline[]         findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PipelineRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        // @phpstan-ignore-next-line
        parent::__construct($registry, Pipeline::class);
    }

    public function customerHasPipeline(Customer $customer): ?int
    {
        $pipeline = $this->createQueryBuilder('n')
            ->select('n.id')
            ->where('n.customer = :customer')
            ->setParameter('customer', $customer)
            ->getQuery()
            ->getOneOrNullResult();

        if (is_array($pipeline) && isset($pipeline['id'])) {
            return (int) $pipeline['id'];
        }
        return null;
    }
}
