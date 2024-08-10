<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\VerificationToken;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method VerificationToken|null find($id, $lockMode = null, $lockVersion = null)
 * @method VerificationToken|null findOneBy(array $criteria, array $orderBy = null)
 * @method VerificationToken[]    findAll()
 * @method VerificationToken[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class VerificationTokenRepository extends BaseRepository
{
    private EntityManagerInterface $entityManager;
    public function __construct(ManagerRegistry $registry, EntityManagerInterface $entityManager)
    {
        parent::__construct($registry, VerificationToken::class);
        $this->entityManager = $entityManager;
    }

    /**
     * @throws Exception
     */
    public function delete(VerificationToken $token): void
    {
        // in order to entityManager->remove() work, Entity id had to be Nullable
        $this->entityManager->getConnection()->executeStatement('DELETE FROM verification_token WHERE id = :id', ['id' => $token->getId()]);
    }
}
