<?php

namespace App\CommandHandler\Note\Edit;

use App\Entity\Customer;
use Symfony\Component\Validator\Constraints as Assert;

class NoteEditInputDto
{
    #[Assert\NotBlank]
    private Customer $customer;

    // --- existing encrypted blobs ---
    private ?string $customerTextAnswerOne = null;
    private ?string $customerTextAnswerTwo = null;
    private ?string $beneficiaryTextAnswerOne = null;
    private ?string $beneficiaryTextAnswerTwo = null;

    // multi-replica (KMS2/KMS3) blobs for each of the four streams
    private ?string $customerTextAnswerOneKMS2 = null;
    private ?string $customerTextAnswerOneKMS3 = null;

    private ?string $customerTextAnswerTwoKMS2 = null;
    private ?string $customerTextAnswerTwoKMS3 = null;

    private ?string $beneficiaryTextAnswerOneKMS2 = null;
    private ?string $beneficiaryTextAnswerOneKMS3 = null;

    private ?string $beneficiaryTextAnswerTwoKMS2 = null;
    private ?string $beneficiaryTextAnswerTwoKMS3 = null;

    // --- questions + answers (unchanged) ---
    private ?string $customerFirstQuestion = null;
    private ?string $customerFirstQuestionAnswer = null;
    private ?string $customerSecondQuestion = null;
    private ?string $customerSecondQuestionAnswer = null;
    private ?string $beneficiaryFirstQuestion = null;
    private ?string $beneficiaryFirstQuestionAnswer = null;
    private ?string $beneficiarySecondQuestion = null;
    private ?string $beneficiarySecondQuestionAnswer = null;

    // --- attempts/lockout + banner ---
    private ?int $attemptCount = null;
    private ?\DateTimeImmutable $lockoutUntil = null;
    private string $customerCongrats = '';

    public function __construct(Customer $customer)
    {
        $this->customer = $customer;
    }

    // ---------------------------
    // Customer
    // ---------------------------
    public function getCustomer(): Customer
    {
        return $this->customer;
    }

    public function setCustomer(Customer $customer): self
    {
        $this->customer = $customer;
        return $this;
    }

    // ---------------------------
    // Encrypted blobs (primary)
    // ---------------------------
    public function getCustomerTextAnswerOne(): ?string
    {
        return $this->customerTextAnswerOne;
    }

    public function setCustomerTextAnswerOne(?string $customerTextAnswerOne): self
    {
        $this->customerTextAnswerOne = $customerTextAnswerOne;
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

    public function getCustomerTextAnswerTwo(): ?string
    {
        return $this->customerTextAnswerTwo;
    }

    public function setCustomerTextAnswerTwo(?string $customerTextAnswerTwo): self
    {
        $this->customerTextAnswerTwo = $customerTextAnswerTwo;
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

    // ---------------------------
    // Encrypted blobs (replicas)
    // ---------------------------
    public function getCustomerTextAnswerOneKMS2(): ?string
    {
        return $this->customerTextAnswerOneKMS2;
    }

    public function setCustomerTextAnswerOneKMS2(?string $customerTextAnswerOneKMS2): self
    {
        $this->customerTextAnswerOneKMS2 = $customerTextAnswerOneKMS2;
        return $this;
    }

    public function getCustomerTextAnswerOneKMS3(): ?string
    {
        return $this->customerTextAnswerOneKMS3;
    }

    public function setCustomerTextAnswerOneKMS3(?string $customerTextAnswerOneKMS3): self
    {
        $this->customerTextAnswerOneKMS3 = $customerTextAnswerOneKMS3;
        return $this;
    }

    public function getCustomerTextAnswerTwoKMS2(): ?string
    {
        return $this->customerTextAnswerTwoKMS2;
    }

    public function setCustomerTextAnswerTwoKMS2(?string $customerTextAnswerTwoKMS2): self
    {
        $this->customerTextAnswerTwoKMS2 = $customerTextAnswerTwoKMS2;
        return $this;
    }

    public function getCustomerTextAnswerTwoKMS3(): ?string
    {
        return $this->customerTextAnswerTwoKMS3;
    }

    public function setCustomerTextAnswerTwoKMS3(?string $customerTextAnswerTwoKMS3): self
    {
        $this->customerTextAnswerTwoKMS3 = $customerTextAnswerTwoKMS3;
        return $this;
    }

    public function getBeneficiaryTextAnswerOneKMS2(): ?string
    {
        return $this->beneficiaryTextAnswerOneKMS2;
    }

    public function setBeneficiaryTextAnswerOneKMS2(?string $beneficiaryTextAnswerOneKMS2): self
    {
        $this->beneficiaryTextAnswerOneKMS2 = $beneficiaryTextAnswerOneKMS2;
        return $this;
    }

    public function getBeneficiaryTextAnswerOneKMS3(): ?string
    {
        return $this->beneficiaryTextAnswerOneKMS3;
    }

    public function setBeneficiaryTextAnswerOneKMS3(?string $beneficiaryTextAnswerOneKMS3): self
    {
        $this->beneficiaryTextAnswerOneKMS3 = $beneficiaryTextAnswerOneKMS3;
        return $this;
    }

    public function getBeneficiaryTextAnswerTwoKMS2(): ?string
    {
        return $this->beneficiaryTextAnswerTwoKMS2;
    }

    public function setBeneficiaryTextAnswerTwoKMS2(?string $beneficiaryTextAnswerTwoKMS2): self
    {
        $this->beneficiaryTextAnswerTwoKMS2 = $beneficiaryTextAnswerTwoKMS2;
        return $this;
    }

    public function getBeneficiaryTextAnswerTwoKMS3(): ?string
    {
        return $this->beneficiaryTextAnswerTwoKMS3;
    }

    public function setBeneficiaryTextAnswerTwoKMS3(?string $beneficiaryTextAnswerTwoKMS3): self
    {
        $this->beneficiaryTextAnswerTwoKMS3 = $beneficiaryTextAnswerTwoKMS3;
        return $this;
    }

    // ---------------------------
    // Questions + Answers
    // ---------------------------
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

    // ---------------------------
    // Attempts/lockout + banner
    // ---------------------------
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
