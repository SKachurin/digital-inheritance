<?php

namespace App\Service;

use App\Repository\CustomerRepository;
use Doctrine\ORM\EntityManagerInterface;

class CustomerCleanupService
{
    public function __construct(
        private readonly CustomerRepository $customerRepository,
        private readonly EntityManagerInterface $em,
    ) {}

    public function deleteMarkedAccounts(): void
    {
        $customersToDelete = $this->customerRepository->findAllMarkedForDeletion();

        foreach ($customersToDelete as $customer) {
            $this->em->remove($customer);
        }

        $this->em->flush();
    }
}
