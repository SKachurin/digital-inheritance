<?php

namespace App\CommandHandler\Beneficiary\Create;

use App\Enum\CustomerSocialAppEnum;
use Symfony\Component\Validator\Constraints as Assert;

class BeneficiaryCreateInputDto
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 64)]
    private string $beneficiaryName;
    #[Assert\Email]
    private ?string $beneficiaryEmail = null;
    #[Assert\Email]
    private ?string $beneficiarySecondEmail = null;
    #[Assert\Length(max: 1000)]
    private ?string $beneficiaryFullName = null;
    #[Assert\Length(max: 64)]
    private ?string $beneficiaryCountryCode = null;
    #[Assert\Length(max: 64)]
    private ?string $beneficiaryFirstPhone = null;
    #[Assert\Length(max: 64)]
    private ?string $beneficiarySecondPhone = null;

    #[Assert\Length(max: 1000)]
    private ?string $beneficiaryFirstQuestion = null;

    #[Assert\Length(max: 1000)]
    private ?string $beneficiaryFirstQuestionAnswer = null;

    #[Assert\Length(max: 1000)]
    private ?string $beneficiarySecondQuestion = null;

    #[Assert\Length(max: 1000)]
    private ?string $beneficiarySecondQuestionAnswer = null;

    private ?string $beneficiaryActionsOrder = null;

    public function __construct()
    {}

    public function getBeneficiaryName(): string
    {
        return $this->beneficiaryName;
    }

    public function setBeneficiaryName(string $beneficiaryName): self
    {
        $this->beneficiaryName = $beneficiaryName;
        return $this;
    }

    public function getBeneficiaryEmail(): ?string
    {
        return $this->beneficiaryEmail;
    }

    public function setBeneficiaryEmail(?string $beneficiaryEmail): self
    {
        $this->beneficiaryEmail = $beneficiaryEmail;
        return $this;
    }

    public function getBeneficiarySecondEmail(): ?string
    {
        return $this->beneficiarySecondEmail;
    }

    public function setBeneficiarySecondEmail(?string $beneficiarySecondEmail): self
    {
        $this->beneficiarySecondEmail = $beneficiarySecondEmail;
        return $this;
    }

    public function getBeneficiaryFullName(): ?string
    {
        return $this->beneficiaryFullName;
    }

    public function setBeneficiaryFullName(?string $beneficiaryFullName): self
    {
        $this->beneficiaryFullName = $beneficiaryFullName;
        return $this;
    }

    public function getBeneficiaryCountryCode(): ?string
    {
        return $this->beneficiaryCountryCode;
    }

    public function setBeneficiaryCountryCode(?string $beneficiaryCountryCode): self
    {
        $this->beneficiaryCountryCode = $beneficiaryCountryCode;
        return $this;
    }

    public function getBeneficiaryFirstPhone(): ?string
    {
        return $this->beneficiaryFirstPhone;
    }

    public function setBeneficiaryFirstPhone(?string $beneficiaryFirstPhone): self
    {
        $this->beneficiaryFirstPhone = $beneficiaryFirstPhone;
        return $this;
    }

    public function getBeneficiarySecondPhone(): ?string
    {
        return $this->beneficiarySecondPhone;
    }

    public function setBeneficiarySecondPhone(?string $beneficiarySecondPhone): self
    {
        $this->beneficiarySecondPhone = $beneficiarySecondPhone;
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

    public function getBeneficiaryActionsOrder(): ?string
    {
        return $this->beneficiaryActionsOrder;
    }

    public function setBeneficiaryActionsOrder(?string $beneficiaryActionsOrder): self
    {
        $this->beneficiaryActionsOrder = $beneficiaryActionsOrder;
        return $this;
    }

}
