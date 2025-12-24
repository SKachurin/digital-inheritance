<?php

namespace App\CommandHandler\Note\Decrypt;

use App\Entity\Note;
use DateTimeImmutable;
use Symfony\Component\Validator\Constraints as Assert;

class BeneficiaryNoteDecryptInputDto
{
    #[Assert\NotBlank]
    private Note $note;

    private ?int $attemptCount = null;
    private ?DateTimeImmutable $lockoutUntil = null;

    // KMS triplets for beneficiary answers
    private ?string $beneficiaryTextAnswerOne = null;
    private ?string $beneficiaryTextAnswerOneKms2 = null;
    private ?string $beneficiaryTextAnswerOneKms3 = null;

    private ?string $beneficiaryTextAnswerTwo = null;
    private ?string $beneficiaryTextAnswerTwoKms2 = null;
    private ?string $beneficiaryTextAnswerTwoKms3 = null;

    private ?string $beneficiaryFirstQuestion = null;
    private ?string $beneficiaryFirstQuestionAnswer = null;

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
}