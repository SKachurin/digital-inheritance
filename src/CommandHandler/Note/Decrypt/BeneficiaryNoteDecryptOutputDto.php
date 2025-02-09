<?php

namespace App\CommandHandler\Note\Decrypt;

use App\Entity\Note;
use DateTimeImmutable;
use Symfony\Component\Validator\Constraints as Assert;

class BeneficiaryNoteDecryptOutputDto
{
    #[Assert\NotBlank]
    private Note $note;
    private ?string $customerText;
    private ?int $attemptCount = null;
    private ?DateTimeImmutable $lockoutUntil = null;
    private ?string $beneficiaryTextAnswerOne = null;
    private ?string $beneficiaryFirstQuestion = null;
    private ?string $beneficiaryFirstQuestionAnswer = null;
    private ?string $beneficiaryTextAnswerTwo = null;
    private ?string $beneficiarySecondQuestion = null;
    private ?string $beneficiarySecondQuestionAnswer = null;
    private string $beneficiaryCongrats = '';

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

    public function setCustomerText(?string $customerText): self
    {
        $this->customerText = $customerText;
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

    public function getBeneficiaryFirstQuestion(): ?string
    {
        return $this->beneficiaryFirstQuestion;
    }

    public function setBeneficiaryFirstQuestion(?string $beneficiaryFirstQuestion): self
    {
        $this->beneficiaryFirstQuestion = $beneficiaryFirstQuestion;
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

    public function getBeneficiarySecondQuestion(): ?string
    {
        return $this->beneficiarySecondQuestion;
    }

    public function setBeneficiarySecondQuestion(?string $beneficiarySecondQuestion): self
    {
        $this->beneficiarySecondQuestion = $beneficiarySecondQuestion;
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

    public function getBeneficiaryCongrats(): string
    {
        return $this->beneficiaryCongrats;
    }

    public function setBeneficiaryCongrats(string $beneficiaryCongrats): self
    {
        $this->beneficiaryCongrats = $beneficiaryCongrats;
        return $this;
    }
}
