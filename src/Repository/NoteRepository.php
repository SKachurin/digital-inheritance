<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Note;
use App\Repository\Collection\PageCollection;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method PageCollection    getAll(int $page = 1, int $pageSize = 100, array $criteria = [])
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
}