<?php

namespace App\CommandHandler\Contact\Edit;

use App\Entity\Customer;
use Symfony\Component\Validator\Constraints as Assert;

class ContactEditInputDto
{
    #[Assert\NotBlank]
    private Customer $customer;

    private int $id;

    private ?string $contactTypeEnum = null;

    private ?string $countryCode = null;

    private ?string $value = null;

    private ?bool $isVerified = null;


    public function __construct(Customer $customer, int $id)
    {
        $this->customer = $customer;
        $this->id = $id;
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

    public function getId(): int
    {
        return $this->id;
    }
    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }
    public function getContactTypeEnum(): ?string
    {
        return $this->contactTypeEnum;
    }
    public function setContactTypeEnum(?string $contactTypeEnum): self
    {
        $this->contactTypeEnum = $contactTypeEnum;
        return $this;
    }
    public function getCountryCode(): ?string
    {
        return $this->countryCode;
    }
    public function setCountryCode(?string $countryCode): self
    {
        $this->countryCode = $countryCode;
        return $this;
    }
    public function getValue(): ?string
    {
        return $this->value;
    }
    public function setValue(?string $value): self
    {
        $this->value = $value;
        return $this;
    }
    public function getIsVerified(): ?bool
    {
        return $this->isVerified;
    }
    public function setIsVerified(?bool $isVerified): self
    {
        $this->isVerified = $isVerified;
        return $this;
    }
}
