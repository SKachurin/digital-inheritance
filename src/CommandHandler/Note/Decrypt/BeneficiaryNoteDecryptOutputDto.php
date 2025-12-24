<?php

namespace App\CommandHandler\Note\Decrypt;

use App\Entity\Note;
use DateTimeImmutable;
use Symfony\Component\Validator\Constraints as Assert;

class BeneficiaryNoteDecryptOutputDto
{
    #[Assert\NotBlank]
    private Note $note;

    private ?string $customerText = null;      // KMS1 display (plain or fallback)
    private ?string $customerTextKMS2 = null;  // KMS2 display
    private ?string $customerTextKMS3 = null;  // KMS3 display

    private bool $decryptionSucceeded = false;

    private ?int $attemptCount = null;
    private ?DateTimeImmutable $lockoutUntil = null;

    private ?string $beneficiaryTextAnswerOne = null;
    private ?string $beneficiaryFirstQuestion = null;
    private ?string $beneficiaryFirstQuestionAnswer = null;

    private ?string $beneficiaryTextAnswerTwo = null;
    private ?string $beneficiarySecondQuestion = null;
    private ?string $beneficiarySecondQuestionAnswer = null;

    private string $beneficiaryCongrats = '';
    private ?int $rateLimitSeconds = null;

    public function __construct(Note $note)
    {
        $this->note = $note;
    }

    public function getNote(): Note
    {
        return $this->note;
    }

    public function setNote(Note $note): self
    {
        $this->note = $note;
        return $this;
    }

    public function getCustomerText(): ?string
    {
        return $this->customerText;
    }

    public function setCustomerText(?string $v): self
    {
        $this->customerText = $v;
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

    public function getBeneficiaryCongrats(): string
    {
        return $this->beneficiaryCongrats;
    }

    public function setBeneficiaryCongrats(string $v): self
    {
        $this->beneficiaryCongrats = $v;
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

    // keep your metadata setters/getters as needed (unchanged)
    public function getBeneficiaryTextAnswerOne(): ?string
    {
        return $this->beneficiaryTextAnswerOne;
    }

    public function setBeneficiaryTextAnswerOne(?string $v): self
    {
        $this->beneficiaryTextAnswerOne = $v;
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

    public function getBeneficiaryFirstQuestion(): ?string
    {
        return $this->beneficiaryFirstQuestion;
    }

    public function setBeneficiaryFirstQuestion(?string $v): self
    {
        $this->beneficiaryFirstQuestion = $v;
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

    public function getBeneficiaryFirstQuestionAnswer(): ?string
    {
        return $this->beneficiaryFirstQuestionAnswer;
    }

    public function setBeneficiaryFirstQuestionAnswer(?string $v): self
    {
        $this->beneficiaryFirstQuestionAnswer = $v;
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
}