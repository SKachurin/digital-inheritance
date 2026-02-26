<?php

namespace App\CommandHandler\Note\Edit;

use App\Entity\Customer;
use Symfony\Component\Validator\Constraints as Assert;

class NoteEditOutputDto
{
    #[Assert\NotBlank]
    private Customer $customer;

    private ?string $customerText = null;

    private ?string $customerTextKms2 = null;
    private ?string $customerTextKms3 = null;

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
    private ?int $rateLimitSeconds = null;

    // NEW: frontend encryption flag + blobs (like create flow)
    private bool $frontendEncrypted = false;

    private ?string $customerTextAnswerOne = null;
    private ?string $customerTextAnswerOneKms2 = null;
    private ?string $customerTextAnswerOneKms3 = null;

    private ?string $customerTextAnswerTwo = null;
    private ?string $customerTextAnswerTwoKms2 = null;
    private ?string $customerTextAnswerTwoKms3 = null;

    private ?string $beneficiaryTextAnswerOne = null;
    private ?string $beneficiaryTextAnswerOneKms2 = null;
    private ?string $beneficiaryTextAnswerOneKms3 = null;

    private ?string $beneficiaryTextAnswerTwo = null;
    private ?string $beneficiaryTextAnswerTwoKms2 = null;
    private ?string $beneficiaryTextAnswerTwoKms3 = null;

    private bool $decryptionSucceeded = false;

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

    public function getCustomerFirstQuestion(): ?string
    {
        return $this->customerFirstQuestion;
    }

    public function setCustomerFirstQuestion(?string $v): self
    {
        $this->customerFirstQuestion = $v;
        return $this;
    }

    public function getCustomerFirstQuestionAnswer(): ?string
    {
        return $this->customerFirstQuestionAnswer;
    }

    public function setCustomerFirstQuestionAnswer(?string $v): self
    {
        $this->customerFirstQuestionAnswer = $v;
        return $this;
    }

    public function getCustomerSecondQuestion(): ?string
    {
        return $this->customerSecondQuestion;
    }

    public function setCustomerSecondQuestion(?string $v): self
    {
        $this->customerSecondQuestion = $v;
        return $this;
    }

    public function getCustomerSecondQuestionAnswer(): ?string
    {
        return $this->customerSecondQuestionAnswer;
    }

    public function setCustomerSecondQuestionAnswer(?string $v): self
    {
        $this->customerSecondQuestionAnswer = $v;
        return $this;
    }

    public function getBeneficiaryFirstQuestion(): ?string
    {
        return $this->beneficiaryFirstQuestion;
    }

    public function setBeneficiaryFirstQuestion(?string $v): self
    {
        $this->beneficiaryFirstQuestion = $v;
        return $this;
    }

    public function getBeneficiaryFirstQuestionAnswer(): ?string
    {
        return $this->beneficiaryFirstQuestionAnswer;
    }

    public function setBeneficiaryFirstQuestionAnswer(?string $v): self
    {
        $this->beneficiaryFirstQuestionAnswer = $v;
        return $this;
    }

    public function getBeneficiarySecondQuestion(): ?string
    {
        return $this->beneficiarySecondQuestion;
    }

    public function setBeneficiarySecondQuestion(?string $v): self
    {
        $this->beneficiarySecondQuestion = $v;
        return $this;
    }

    public function getBeneficiarySecondQuestionAnswer(): ?string
    {
        return $this->beneficiarySecondQuestionAnswer;
    }

    public function setBeneficiarySecondQuestionAnswer(?string $v): self
    {
        $this->beneficiarySecondQuestionAnswer = $v;
        return $this;
    }

    public function getCustomerCongrats(): string
    {
        return $this->customerCongrats;
    }

    public function setCustomerCongrats(string $v): self
    {
        $this->customerCongrats = $v;
        return $this;
    }

    public function getAttemptCount(): ?int
    {
        return $this->attemptCount;
    }

    public function setAttemptCount(?int $v): self
    {
        $this->attemptCount = $v;
        return $this;
    }

    public function getLockoutUntil(): ?\DateTimeImmutable
    {
        return $this->lockoutUntil;
    }

    public function setLockoutUntil(?\DateTimeImmutable $v): self
    {
        $this->lockoutUntil = $v;
        return $this;
    }

    public function getRateLimitSeconds(): ?int
    {
        return $this->rateLimitSeconds;
    }

    public function setRateLimitSeconds(?int $v): self
    {
        $this->rateLimitSeconds = $v;
        return $this;
    }

    // NEW
    public function isFrontendEncrypted(): bool
    {
        return $this->frontendEncrypted;
    }

    public function setFrontendEncrypted(bool|string|int|null $v): self
    {
        $this->frontendEncrypted = ($v === true || $v === 1 || $v === '1');
        return $this;
    }

    // 12 blobs getters/setters
    public function getCustomerTextAnswerOne(): ?string
    {
        return $this->customerTextAnswerOne;
    }

    public function setCustomerTextAnswerOne(?string $v): self
    {
        $this->customerTextAnswerOne = $v;
        return $this;
    }

    public function getCustomerTextAnswerOneKms2(): ?string
    {
        return $this->customerTextAnswerOneKms2;
    }

    public function setCustomerTextAnswerOneKms2(?string $v): self
    {
        $this->customerTextAnswerOneKms2 = $v;
        return $this;
    }

    public function getCustomerTextAnswerOneKms3(): ?string
    {
        return $this->customerTextAnswerOneKms3;
    }

    public function setCustomerTextAnswerOneKms3(?string $v): self
    {
        $this->customerTextAnswerOneKms3 = $v;
        return $this;
    }

    public function getCustomerTextAnswerTwo(): ?string
    {
        return $this->customerTextAnswerTwo;
    }

    public function setCustomerTextAnswerTwo(?string $v): self
    {
        $this->customerTextAnswerTwo = $v;
        return $this;
    }

    public function getCustomerTextAnswerTwoKms2(): ?string
    {
        return $this->customerTextAnswerTwoKms2;
    }

    public function setCustomerTextAnswerTwoKms2(?string $v): self
    {
        $this->customerTextAnswerTwoKms2 = $v;
        return $this;
    }

    public function getCustomerTextAnswerTwoKms3(): ?string
    {
        return $this->customerTextAnswerTwoKms3;
    }

    public function setCustomerTextAnswerTwoKms3(?string $v): self
    {
        $this->customerTextAnswerTwoKms3 = $v;
        return $this;
    }

    public function getBeneficiaryTextAnswerOne(): ?string
    {
        return $this->beneficiaryTextAnswerOne;
    }

    public function setBeneficiaryTextAnswerOne(?string $v): self
    {
        $this->beneficiaryTextAnswerOne = $v;
        return $this;
    }

    public function getBeneficiaryTextAnswerOneKms2(): ?string
    {
        return $this->beneficiaryTextAnswerOneKms2;
    }

    public function setBeneficiaryTextAnswerOneKms2(?string $v): self
    {
        $this->beneficiaryTextAnswerOneKms2 = $v;
        return $this;
    }

    public function getBeneficiaryTextAnswerOneKms3(): ?string
    {
        return $this->beneficiaryTextAnswerOneKms3;
    }

    public function setBeneficiaryTextAnswerOneKms3(?string $v): self
    {
        $this->beneficiaryTextAnswerOneKms3 = $v;
        return $this;
    }

    public function getBeneficiaryTextAnswerTwo(): ?string
    {
        return $this->beneficiaryTextAnswerTwo;
    }

    public function setBeneficiaryTextAnswerTwo(?string $v): self
    {
        $this->beneficiaryTextAnswerTwo = $v;
        return $this;
    }

    public function getBeneficiaryTextAnswerTwoKms2(): ?string
    {
        return $this->beneficiaryTextAnswerTwoKms2;
    }

    public function setBeneficiaryTextAnswerTwoKms2(?string $v): self
    {
        $this->beneficiaryTextAnswerTwoKms2 = $v;
        return $this;
    }

    public function getBeneficiaryTextAnswerTwoKms3(): ?string
    {
        return $this->beneficiaryTextAnswerTwoKms3;
    }

    public function setBeneficiaryTextAnswerTwoKms3(?string $v): self
    {
        $this->beneficiaryTextAnswerTwoKms3 = $v;
        return $this;
    }

    public function getCustomerTextKms2(): ?string
    {
        return $this->customerTextKms2;
    }

    public function setCustomerTextKms2(?string $v): self
    {
        $this->customerTextKms2 = $v;
        return $this;
    }

    public function getCustomerTextKms3(): ?string
    {
        return $this->customerTextKms3;
    }

    public function setCustomerTextKms3(?string $v): self
    {
        $this->customerTextKms3 = $v;
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
}
