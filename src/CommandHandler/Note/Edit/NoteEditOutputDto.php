<?php

namespace App\CommandHandler\Note\Edit;

use App\Entity\Customer;
use Symfony\Component\Validator\Constraints as Assert;

class NoteEditOutputDto
{
    #[Assert\NotBlank]
    private Customer $customer;
    private ?string $customerText;
    private ?string $customerFirstQuestion = null;
    private ?string $customerFirstQuestionAnswer = null;
    private ?string $customerSecondQuestion = null;
    private ?string $customerSecondQuestionAnswer = null;
    private ?string $beneficiaryFirstQuestion = null;
    private ?string $beneficiaryFirstQuestionAnswer = null;
    private ?string $beneficiarySecondQuestion = null;
    private ?string $beneficiarySecondQuestionAnswer = null;
    private string $customerCongrats = '';
    private ?int $attemptCount = null;
    private ?\DateTimeImmutable $lockoutUntil = null;


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

    public function getCustomerFirstQuestion(): ?string
    {
        return $this->customerFirstQuestion;
    }

    public function setCustomerFirstQuestion(?string $customerFirstQuestion): self
    {
        $this->customerFirstQuestion = $customerFirstQuestion;
        return $this;
    }

    public function getBeneficiaryFirstQuestion(): ?string
    {
        return $this->beneficiaryFirstQuestion;
    }

    public function setBeneficiaryFirstQuestion(?string $beneficiaryFirstQuestion): self
    {
        $this->beneficiaryFirstQuestion = $beneficiaryFirstQuestion;
        return $this;
    }

    public function getCustomerFirstQuestionAnswer(): ?string
    {
        return $this->customerFirstQuestionAnswer;
    }

    public function setCustomerFirstQuestionAnswer(?string $customerFirstQuestionAnswer): self
    {
        $this->customerFirstQuestionAnswer = $customerFirstQuestionAnswer;
        return $this;
    }

    public function getBeneficiaryFirstQuestionAnswer(): ?string
    {
        return $this->beneficiaryFirstQuestionAnswer;
    }

    public function setBeneficiaryFirstQuestionAnswer(?string $beneficiaryFirstQuestionAnswer): self
    {
        $this->beneficiaryFirstQuestionAnswer = $beneficiaryFirstQuestionAnswer;
        return $this;
    }

    public function getCustomerSecondQuestion(): ?string
    {
        return $this->customerSecondQuestion;
    }

    public function setCustomerSecondQuestion(?string $customerSecondQuestion): self
    {
        $this->customerSecondQuestion = $customerSecondQuestion;
        return $this;
    }

    public function getBeneficiarySecondQuestion(): ?string
    {
        return $this->beneficiarySecondQuestion;
    }

    public function setBeneficiarySecondQuestion(?string $beneficiarySecondQuestion): self
    {
        $this->beneficiarySecondQuestion = $beneficiarySecondQuestion;
        return $this;
    }

    public function getCustomerSecondQuestionAnswer(): ?string
    {
        return $this->customerSecondQuestionAnswer;
    }

    public function setCustomerSecondQuestionAnswer(?string $customerSecondQuestionAnswer): self
    {
        $this->customerSecondQuestionAnswer = $customerSecondQuestionAnswer;
        return $this;
    }

    public function getBeneficiarySecondQuestionAnswer(): ?string
    {
        return $this->beneficiarySecondQuestionAnswer;
    }

    public function setBeneficiarySecondQuestionAnswer(?string $beneficiarySecondQuestionAnswer): self
    {
        $this->beneficiarySecondQuestionAnswer = $beneficiarySecondQuestionAnswer;
        return $this;
    }

    public function getAttemptCount(): ?int
    {
        return $this->attemptCount;
    }
    public function setAttemptCount(?int $attemptCount): self
    {
        $this->attemptCount = $attemptCount;
        return $this;
    }

    public function setLockoutUntil(?\DateTimeImmutable $dateTime): self
    {
        $this->lockoutUntil = $dateTime;
        return $this;
    }

    public function getLockoutUntil(): ?\DateTimeImmutable
    {
        return $this->lockoutUntil;
    }
}
