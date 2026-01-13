<?php

namespace App\Repository;

use App\Entity\Customer;
use App\Entity\Kms;
use App\Entity\Note;
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

    /**
     * @return Kms[]
     */
    public function findUsedByCustomer(Customer $customer): array
    {
        /** @var Note|null $note */
        $note = $this->getEntityManager()
            ->getRepository(Note::class)
            ->findOneBy(['customer' => $customer]);

        if (!$note) {
            return [];
        }

        // Collect note fields that may contain {"kms_id":"kmsX", ...}
        $fields = [
            $note->getCustomerTextAnswerOne(),
            $note->getCustomerTextAnswerTwo(),
            $note->getBeneficiaryTextAnswerOne(),
            $note->getBeneficiaryTextAnswerTwo(),

            $note->getCustomerTextAnswerOneKms2(),
            $note->getCustomerTextAnswerOneKms3(),
            $note->getCustomerTextAnswerTwoKms2(),
            $note->getCustomerTextAnswerTwoKms3(),

            $note->getBeneficiaryTextAnswerOneKms2(),
            $note->getBeneficiaryTextAnswerOneKms3(),
            $note->getBeneficiaryTextAnswerTwoKms2(),
            $note->getBeneficiaryTextAnswerTwoKms3(),
        ];

        $aliases = [];

        foreach ($fields as $json) {
            if (!is_string($json) || $json === '') {
                continue;
            }

            $decoded = json_decode($json, true);
            if (!is_array($decoded)) {
                continue;
            }

            $kmsId = $decoded['kms_id'] ?? null;
            if (is_string($kmsId) && $kmsId !== '') {
                $aliases[$kmsId] = true;
            }
        }

        $aliases = array_keys($aliases);

        if ($aliases === []) {
            return [];
        }

        return $this->createQueryBuilder('k')
            ->where('k.alias IN (:aliases)')
            ->setParameter('aliases', $aliases)
            ->getQuery()
            ->getResult();
    }
}
