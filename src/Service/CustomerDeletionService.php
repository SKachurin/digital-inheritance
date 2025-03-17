<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Customer;
use App\Repository\VerificationTokenRepository;
use Doctrine\ORM\EntityManagerInterface;

class CustomerDeletionService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly VerificationTokenRepository $tokenRepo,
    )
    {
    }

    public function processToken(string $token, Customer $customer): string
    {
        $tokenEntity = $this->tokenRepo->findOneBy(['token' => $token]);

        if (!$tokenEntity || $tokenEntity->isExpired()) {
            return 'token';
        }
        if ($tokenEntity->getContact()->getCustomer() !== $customer ) {
            return 'customer';
        }

        $this->em->remove($tokenEntity);

        $customer->setDeletedAtValue(new \DateTimeImmutable());

        $this->em->persist($customer);
        $this->em->flush();

        return 'success';
    }

    public function cancelDeletion(Customer $customer): void
    {
        $customer->setDeletedAtValue(null);

        $this->em->persist($customer);
        $this->em->flush();
    }
}
