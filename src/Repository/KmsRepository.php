<?php

namespace App\Repository;

use App\Entity\Kms;
use Doctrine\Persistence\ManagerRegistry;

class KmsRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Kms::class);
    }

    /**
     * @param string[] $aliases
     * @return Kms[]
     */
    public function findByAliases(array $aliases): array
    {
        if ($aliases === []) {
            return [];
        }

        return $this->createQueryBuilder('k')
            ->where('k.alias IN (:aliases)')
            ->setParameter('aliases', $aliases)
            ->getQuery()
            ->getResult();
    }

    public function findOneByAlias(string $alias): ?Kms
    {
        return $this->findOneBy(['alias' => $alias]);
    }
}
