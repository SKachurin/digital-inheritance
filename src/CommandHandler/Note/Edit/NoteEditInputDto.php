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
    private ?string $customerTextAnswerOneKms2 = null;
    private ?string $customerTextAnswerOneKms3 = null;

    private ?string $customerTextAnswerTwoKms2 = null;
    private ?string $customerTextAnswerTwoKms3 = null;

    private ?string $beneficiaryTextAnswerOneKms2 = null;
    private ?string $beneficiaryTextAnswerOneKms3 = null;

    private ?string $beneficiaryTextAnswerTwoKms2 = null;
    private ?string $beneficiaryTextAnswerTwoKms3 = null;

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
    public function getCustomerTextAnswerOneKms2(): ?string
    {
        return $this->customerTextAnswerOneKms2;
    }

    public function setCustomerTextAnswerOneKms2(?string $customerTextAnswerOneKms2): self
    {
        $this->customerTextAnswerOneKms2 = $customerTextAnswerOneKms2;
        return $this;
    }

    public function getCustomerTextAnswerOneKms3(): ?string
    {
        return $this->customerTextAnswerOneKms3;
    }

    public function setCustomerTextAnswerOneKms3(?string $customerTextAnswerOneKms3): self
    {
        $this->customerTextAnswerOneKms3 = $customerTextAnswerOneKms3;
        return $this;
    }

    public function getCustomerTextAnswerTwoKms2(): ?string
    {
        return $this->customerTextAnswerTwoKms2;
    }

    public function setCustomerTextAnswerTwoKms2(?string $customerTextAnswerTwoKms2): self
    {
        $this->customerTextAnswerTwoKms2 = $customerTextAnswerTwoKms2;
        return $this;
    }

    public function getCustomerTextAnswerTwoKms3(): ?string
    {
        return $this->customerTextAnswerTwoKms3;
    }

    public function setCustomerTextAnswerTwoKms3(?string $customerTextAnswerTwoKms3): self
    {
        $this->customerTextAnswerTwoKms3 = $customerTextAnswerTwoKms3;
        return $this;
    }

    public function getBeneficiaryTextAnswerOneKms2(): ?string
    {
        return $this->beneficiaryTextAnswerOneKms2;
    }

    public function setBeneficiaryTextAnswerOneKms2(?string $beneficiaryTextAnswerOneKms2): self
    {
        $this->beneficiaryTextAnswerOneKms2 = $beneficiaryTextAnswerOneKms2;
        return $this;
    }

    public function getBeneficiaryTextAnswerOneKms3(): ?string
    {
        return $this->beneficiaryTextAnswerOneKms3;
    }

    public function setBeneficiaryTextAnswerOneKms3(?string $beneficiaryTextAnswerOneKms3): self
    {
        $this->beneficiaryTextAnswerOneKms3 = $beneficiaryTextAnswerOneKms3;
        return $this;
    }

    public function getBeneficiaryTextAnswerTwoKms2(): ?string
    {
        return $this->beneficiaryTextAnswerTwoKms2;
    }

    public function setBeneficiaryTextAnswerTwoKms2(?string $beneficiaryTextAnswerTwoKms2): self
    {
        $this->beneficiaryTextAnswerTwoKms2 = $beneficiaryTextAnswerTwoKms2;
        return $this;
    }

    public function getBeneficiaryTextAnswerTwoKms3(): ?string
    {
        return $this->beneficiaryTextAnswerTwoKms3;
    }

    public function setBeneficiaryTextAnswerTwoKms3(?string $beneficiaryTextAnswerTwoKms3): self
    {
        $this->beneficiaryTextAnswerTwoKms3 = $beneficiaryTextAnswerTwoKms3;
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
