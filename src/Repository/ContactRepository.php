<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Contact;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Contact|null find($id, $lockMode = null, $lockVersion = null)
 * @method Contact|null findOneBy(array $criteria, array $orderBy = null)
 * @method Contact      getOneBy(array $criteria, array $orderBy = null)
 * @method Contact[]    findAll()
 * @method Contact[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ContactRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Contact::class);
    }

    /**
     * Get verified contacts of a specific type for a given customer
     *
     * @return Contact[]
     */
    public function findVerifiedContactsByType(int $customerId, string $contactType): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.customer = :customerId')
            ->andWhere('c.contactTypeEnum = :contactType')
            ->andWhere('c.isVerified = true')
            ->setParameter('customerId', $customerId)
            ->setParameter('contactType', $contactType)
            ->getQuery()
            ->getResult();
    }

}
