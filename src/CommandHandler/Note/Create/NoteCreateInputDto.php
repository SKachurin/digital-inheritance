<?php

namespace App\CommandHandler\Note\Create;

use App\Entity\Customer;
use Symfony\Component\Validator\Constraints as Assert;

class NoteCreateInputDto
{
    #[Assert\NotBlank]
    private Customer $customer;

    #[Assert\NotBlank]
    private ?string $customerText;

    private ?int $pipelineId;

    #[Assert\Length(max: 10000)]
    private ?string $customerTextAnswerOne;

    #[Assert\Length(max: 10000)]
    private ?string $customerTextAnswerTwo;

    private ?int $beneficiaryId;

    #[Assert\Length(max: 10000)]
    private ?string $beneficiaryTextAnswerOne;

    #[Assert\Length(max: 10000)]
    private ?string $beneficiaryTextAnswerTwo;

    public function __construct(
        Customer $customer,
        ?string $customerText = null,

    ) {
        $this->customer = $customer;
        $this->customerText = $customerText;
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

    public function getPipelineId(): ?int
    {
        return $this->pipelineId;
    }

    public function setPipelineId(int $pipelineId): self
    {
        $this->pipelineId = $pipelineId;
        return $this;
    }

    public function getCustomerTextAnswerOne(): ?string
    {
        return $this->customerTextAnswerOne;
    }

    public function setCustomerTextAnswerOne(?string $customerTextAnswerOne): self
    {
        $this->customerTextAnswerOne = $customerTextAnswerOne;
        return $this;
    }

    public function getCustomerTextAnswerTwo(): ?string
    {
        return $this->customerTextAnswerTwo;
    }

    public function setCustomerTextAnswerTwo(?string $customerTextAnswerTwo): self
    {
        $this->customerTextAnswerTwo = $customerTextAnswerTwo;
        return $this;
    }

    public function getBeneficiaryId(): ?int
    {
        return $this->beneficiaryId;
    }

    public function setBeneficiaryId(?int $beneficiaryId): self
    {
        $this->beneficiaryId = $beneficiaryId;
        return $this;
    }

    public function getBeneficiaryTextAnswerOne(): ?string
    {
        return $this->beneficiaryTextAnswerOne;
    }

    public function setBeneficiaryTextAnswerOne(?string $beneficiaryTextAnswerOne): self
    {
        $this->beneficiaryTextAnswerOne = $beneficiaryTextAnswerOne;
        return $this;
    }

    public function getBeneficiaryTextAnswerTwo(): ?string
    {
        return $this->beneficiaryTextAnswerTwo;
    }

    public function setBeneficiaryTextAnswerTwo(?string $beneficiaryTextAnswerTwo): self
    {
        $this->beneficiaryTextAnswerTwo = $beneficiaryTextAnswerTwo;
        return $this;
    }
}
