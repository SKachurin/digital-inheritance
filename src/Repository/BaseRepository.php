<?php

declare(strict_types=1);

namespace App\Repository;

use Doctrine\ORM\EntityNotFoundException;
use App\Repository\Collection\PageCollection;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Query\QueryException;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @extends ServiceEntityRepository<BaseRepository>
 */
abstract class BaseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, string $entityClass)
    {
        parent::__construct($registry, $entityClass);
    }

    /**
     * @param array<Criteria> $criteria
     *
     * @throws QueryException
     */
    public function getAll(
        int $page = 1,
        int $pageSize = 100,
        array $criteria = [],
    ): PageCollection {
        $queryBuilder = $this->createQueryBuilder('e');

        // in descending order
        $queryBuilder->orderBy('e.created_at', 'DESC')
            ->setFirstResult($pageSize * ($page - 1))
            ->setMaxResults($pageSize)
        ;

        $query = $queryBuilder->getQuery();
        $pagination = new Paginator($query);

        return new PageCollection(
            current: $page,
            pageSize: $pageSize,
            totalPages: (int) ceil((int) $pagination->count() / $pageSize),
            totalItems: (int) $pagination->count(), // $totalCount, //
            items: (array) iterator_to_array($pagination->getIterator())
        );
    }

    public function getOneBy(array $criteria, ?array $orderBy = null): object
    {
        $entity = $this->findOneBy($criteria, $orderBy);
        if (null === $entity) {
            throw new EntityNotFoundException("Entity {$this->getClassMetadata()->getName()} not found");
        }

        return $entity;
    }
}
