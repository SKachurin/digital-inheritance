<?php

namespace App\CommandHandler\Beneficiary\Create;

use App\Entity\Customer;
use App\Enum\CustomerSocialAppEnum;
use Symfony\Component\Validator\Constraints as Assert;

class BeneficiaryCreateInputDto
{
    #[Assert\NotBlank]
    private Customer $customer;
    #[Assert\Length(max: 512)]
    private ?string $customerFullName = null;
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 64)]
    private string $beneficiaryName;
    #[Assert\Email]
    private ?string $beneficiaryEmail = null;
    #[Assert\Email]
    private ?string $beneficiarySecondEmail = null;
    #[Assert\Length(max: 1000)]
    private ?string $beneficiaryFullName = null;
    #[Assert\Length(max: 64)]
    private ?string $beneficiaryCountryCode = null;
    #[Assert\Length(max: 64)]
    private ?string $beneficiaryFirstPhone = null;
    #[Assert\Length(max: 64)]
    private ?string $beneficiarySecondPhone = null;

    private ?string $beneficiaryActionsOrder = null;

    #[Assert\Length(max: 5)]
    private ?string $beneficiaryLang = null;

    public function __construct(Customer $customer)
    {
        $this->customer = $customer;
    }

    public function getCustomer(): Customer
    {
        return $this->customer;
    }

    public function setCustomer(Customer $customer): self
    {
        $this->customer = $customer;
        return $this;
    }

    public function getCustomerFullName(): ?string
    {
        return $this->customerFullName;
    }

    public function setCustomerFullName(string $customerFullName): self
    {
        $this->customerFullName = $customerFullName;
        return $this;
    }

    public function getBeneficiaryName(): string
    {
        return $this->beneficiaryName;
    }

    public function setBeneficiaryName(string $beneficiaryName): self
    {
        $this->beneficiaryName = $beneficiaryName;
        return $this;
    }

    public function getBeneficiaryEmail(): ?string
    {
        return $this->beneficiaryEmail;
    }

    public function setBeneficiaryEmail(?string $beneficiaryEmail): self
    {
        $this->beneficiaryEmail = $beneficiaryEmail;
        return $this;
    }

    public function getBeneficiarySecondEmail(): ?string
    {
        return $this->beneficiarySecondEmail;
    }

    public function setBeneficiarySecondEmail(?string $beneficiarySecondEmail): self
    {
        $this->beneficiarySecondEmail = $beneficiarySecondEmail;
        return $this;
    }

    public function getBeneficiaryFullName(): ?string
    {
        return $this->beneficiaryFullName;
    }

    public function setBeneficiaryFullName(?string $beneficiaryFullName): self
    {
        $this->beneficiaryFullName = $beneficiaryFullName;
        return $this;
    }

    public function getBeneficiaryCountryCode(): ?string
    {
        return $this->beneficiaryCountryCode;
    }

    public function setBeneficiaryCountryCode(?string $beneficiaryCountryCode): self
    {
        $this->beneficiaryCountryCode = $beneficiaryCountryCode;
        return $this;
    }

    public function getBeneficiaryFirstPhone(): ?string
    {
        return $this->beneficiaryFirstPhone;
    }

    public function setBeneficiaryFirstPhone(?string $beneficiaryFirstPhone): self
    {
        $this->beneficiaryFirstPhone = $beneficiaryFirstPhone;
        return $this;
    }

    public function getBeneficiarySecondPhone(): ?string
    {
        return $this->beneficiarySecondPhone;
    }

    public function setBeneficiarySecondPhone(?string $beneficiarySecondPhone): self
    {
        $this->beneficiarySecondPhone = $beneficiarySecondPhone;
        return $this;
    }

    public function getBeneficiaryActionsOrder(): ?string
    {
        return $this->beneficiaryActionsOrder;
    }

    public function setBeneficiaryActionsOrder(?string $beneficiaryActionsOrder): self
    {
        $this->beneficiaryActionsOrder = $beneficiaryActionsOrder;
        return $this;
    }

    public function getBeneficiaryLang(): ?string
    {
        return $this->beneficiaryLang;
    }

    public function setBeneficiaryLang(?string $beneficiaryLang): self
    {
        $this->beneficiaryLang = $beneficiaryLang;
        return $this;
    }

}
