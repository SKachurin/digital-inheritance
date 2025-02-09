<?php

namespace App\CommandHandler\Note\Decrypt;

use App\Entity\Customer;
use Symfony\Component\Validator\Constraints as Assert;

class NoteDecryptOutputDto // TODO Looks like legacy to me
{
    #[Assert\NotBlank]
    private Customer $customer;

    private ?string $customerText;

    private string $customerCongrats = 'If you can read your text You done everything right!';


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

    public function getCustomerCongrats(): string
    {
        return $this->customerCongrats;
    }

    public function setCustomerCongrats(string $customerCongrats): self
    {
        $this->customerCongrats = $customerCongrats;
        return $this;
    }

}
