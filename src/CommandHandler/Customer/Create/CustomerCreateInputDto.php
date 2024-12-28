<?php

namespace App\CommandHandler\Customer\Create;

use Symfony\Component\Validator\Constraints as Assert;

class CustomerCreateInputDto
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 64)]
    private string $customerName;
    #[Assert\NotBlank]
    #[Assert\Email]
    private string $customerEmail;
    #[Assert\NotBlank]
    private string $password;

    public function __construct(
        string  $customerName,
        string  $customerEmail,
        string  $password,
    )
    {
        $this->customerName = $customerName;
        $this->customerEmail = $customerEmail;
        $this->password = $password;
    }

    public function getCustomerName(): string
    {
        return $this->customerName;
    }
    public function setCustomerName(string $customerName): void
    {
        $this->customerName = $customerName;
    }

    public function getCustomerEmail(): string
    {
        return $this->customerEmail;
    }

    public function setCustomerEmail(string $customerEmail): void
    {
        $this->customerEmail = $customerEmail;
    }

    public function getPassword(): string
    {
        return $this->password;
    }
    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

}

