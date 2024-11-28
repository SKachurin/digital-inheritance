<?php

declare(strict_types=1);

namespace App\CommandHandler\Beneficiary\Delete;

use App\Entity\Customer;
use Symfony\Component\Validator\Constraints as Assert;

class BeneficiaryDeleteInputDto
{
    #[Assert\NotBlank]
    private Customer $customer;

    #[Assert\NotBlank]
    #[Assert\Type('integer')]
    private int $beneficiaryId;

    public function __construct(Customer $customer, int $beneficiaryId)
    {
        $this->customer = $customer;
        $this->beneficiaryId = $beneficiaryId;
    }

    public function getCustomer(): Customer
    {
        return $this->customer;
    }

    public function getBeneficiaryId(): int
    {
        return $this->beneficiaryId;
    }

    public function setBeneficiaryId(int $beneficiaryId): self
    {
        $this->beneficiaryId = $beneficiaryId;
        return $this;
    }
}
