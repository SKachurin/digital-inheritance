<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\VerificationToken;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<VerificationToken>
 *
 * @method VerificationToken|null find($id, $lockMode = null, $lockVersion = null)
 * @method VerificationToken|null findOneBy(array $criteria, array $orderBy = null)
 * @method VerificationToken[]    findAll()
 * @method VerificationToken[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class VerificationTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VerificationToken::class);
    }

}
