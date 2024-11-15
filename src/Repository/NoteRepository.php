<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Customer;
use App\Entity\Note;
use App\Repository\Collection\PageCollection;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method PageCollection   getAll(int $page = 1, int $pageSize = 100, array $criteria = [])
 * @method Note            getOneBy(array $criteria, array $orderBy = null)
 * @method null|Note       find($id, $lockMode = null, $lockVersion = null)
 * @method null|Note       findOneBy(array $criteria, array $orderBy = null)
 * @method Note[]         findAll()
 * @method Note[]         findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NoteRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        // @phpstan-ignore-next-line
        parent::__construct($registry, Note::class);
    }

    public function customerHasNote(Customer $customer): ?int
    {
        $note = $this->createQueryBuilder('n')
            ->select('n.id')
            ->where('n.customer = :customer')
            ->setParameter('customer', $customer->getId())
            ->getQuery()
            ->getOneOrNullResult();

        if (is_array($note) && isset($note['id'])) {
            return (int) $note['id'];
        }
        return null;

    }

    public function customerHasNoteWithBeneficiary(Customer $customer): ?Note
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.customer = :customer')
            ->andWhere('n.beneficiary IS NOT NULL')
            ->setParameter('customer', $customer)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function delete(Note $note): void
    {
        $em = $this->getEntityManager();
        $em->remove($note);
        $em->flush();
//        $em->clear();
//        $em->detach($note);
    }
}
