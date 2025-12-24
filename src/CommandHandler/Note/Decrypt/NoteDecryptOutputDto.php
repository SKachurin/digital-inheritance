<?php

namespace App\CommandHandler\Note\Decrypt;

use App\Entity\Customer;
use Symfony\Component\Validator\Constraints as Assert;

class NoteDecryptOutputDto
{
    #[Assert\NotBlank]
    private Customer $customer;

    private ?string $customerText = null;      // KMS1 display (plain or fallback)
    private ?string $customerTextKMS2 = null;  // KMS2 display (plain or fallback)
    private ?string $customerTextKMS3 = null;  // KMS3 display (plain or fallback)

    private bool $decryptionSucceeded = false;

    private string $customerCongrats = 'If you can read your text You done everything right!';
    private ?int $rateLimitSeconds = null;

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

    public function getCustomerText(): ?string
    {
        return $this->customerText;
    }

    public function setCustomerText(?string $customerText): self
    {
        $this->customerText = $customerText;
        return $this;
    }

    public function getCustomerTextKMS2(): ?string
    {
        return $this->customerTextKMS2;
    }

    public function setCustomerTextKMS2(?string $v): self
    {
        $this->customerTextKMS2 = $v;
        return $this;
    }

    public function getCustomerTextKMS3(): ?string
    {
        return $this->customerTextKMS3;
    }

    public function setCustomerTextKMS3(?string $v): self
    {
        $this->customerTextKMS3 = $v;
        return $this;
    }

    public function isDecryptionSucceeded(): bool
    {
        return $this->decryptionSucceeded;
    }

    public function setDecryptionSucceeded(bool $v): self
    {
        $this->decryptionSucceeded = $v;
        return $this;
    }

    public function getCustomerCongrats(): string
    {
        return $this->customerCongrats;
    }

    public function setCustomerCongrats(string $customerCongrats): self
    {
        $this->customerCongrats = $customerCongrats;
        return $this;
    }

    public function getRateLimitSeconds(): ?int
    {
        return $this->rateLimitSeconds;
    }

    public function setRateLimitSeconds(?int $rateLimitSeconds): self
    {
        $this->rateLimitSeconds = $rateLimitSeconds;
        return $this;
    }
}