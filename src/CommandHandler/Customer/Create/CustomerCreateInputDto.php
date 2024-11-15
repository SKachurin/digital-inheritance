<?php

namespace App\CommandHandler\Customer\Create;

use App\Enum\CustomerSocialAppEnum;
use Symfony\Component\Validator\Constraints as Assert;

class CustomerCreateInputDto
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 64)]
    private string $customerName;
    #[Assert\NotBlank]
    #[Assert\Email]
    private string $customerEmail;
//    #[Assert\Email]
//    private ?string $customerSecondEmail;
//    private ?string $customerFullName;
//    private ?string $customerCountryCode;
//    private ?string $customerFirstPhone;
//    private ?string $customerSecondPhone;
//    #[Assert\NotBlank]
//    private ?string $customerFirstQuestion;
//    #[Assert\NotBlank]
//    private string $customerFirstQuestionAnswer;
//    private ?string $customerSecondQuestion;
//    private ?string $customerSecondQuestionAnswer;
//    private ?CustomerSocialAppEnum $customerSocialApp;
//    private ?string $customerSocialAppLink;
//    #[Assert\NotBlank]
//    private string $customerOkayPassword;
    #[Assert\NotBlank]
    private string $password;

    public function __construct(
        string  $customerName,
        string  $customerEmail,
//        string  $customerFirstQuestion,
//        string  $customerFirstQuestionAnswer,
//        string  $customerOkayPassword,
        string  $password,
//        CustomerSocialAppEnum $customerSocialApp = CustomerSocialAppEnum::NONE,
//        ?string $customerSocialAppLink = null,
//        ?string $customerSecondEmail = null,
//        ?string $customerFullName = null,
//        ?string $customerCountryCode = null,
//        ?string $customerFirstPhone = null,
//        ?string $customerSecondPhone = null,
//        ?string $customerSecondQuestion = null,
//        ?string $customerSecondQuestionAnswer = null

    )
    {
        $this->customerName = $customerName;
        $this->customerEmail = $customerEmail;
//        $this->customerFirstQuestion = $customerFirstQuestion;
//        $this->customerFirstQuestionAnswer = $customerFirstQuestionAnswer;
//        $this->customerOkayPassword = $customerOkayPassword;
        $this->password = $password;
//        $this->customerSocialApp = $customerSocialApp;
//        $this->customerSocialAppLink = $customerSocialAppLink;
//        $this->customerSecondEmail = $customerSecondEmail;
//        $this->customerFullName = $customerFullName;
//        $this->customerCountryCode = $customerCountryCode;
//        $this->customerFirstPhone = $customerFirstPhone;
//        $this->customerSecondPhone = $customerSecondPhone;
//        $this->customerSecondQuestion = $customerSecondQuestion;
//        $this->customerSecondQuestionAnswer = $customerSecondQuestionAnswer;
    }

    public function getCustomerName(): string
    {
        return $this->customerName;
    }
    public function setCustomerName(string $customerName): void
    {
        $this->customerName = $customerName;
    }

    public function getCustomerEmail(): string
    {
        return $this->customerEmail;
    }

    public function setCustomerEmail(string $customerEmail): void
    {
        $this->customerEmail = $customerEmail;
    }

    public function getPassword(): string
    {
        return $this->password;
    }
    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

//    public function getCustomerSecondEmail(): ?string
//    {
//        return $this->customerSecondEmail;
//    }
//
//    public function setCustomerSecondEmail(?string $customerSecondEmail): void
//    {
//        $this->customerSecondEmail = $customerSecondEmail;
//    }

//    public function getCustomerFullName(): ?string
//    {
//        return $this->customerFullName;
//    }
//    public function setCustomerFullName(?string $customerFullName): void
//    {
//        $this->customerFullName = $customerFullName;
//    }

//    public function getCustomerCountryCode(): ?string
//    {
//        return $this->customerCountryCode;
//    }
//    public function setCustomerCountryCode(?string $customerCountryCode): void
//    {
//        $this->customerCountryCode = $customerCountryCode;
//    }

//    public function getCustomerFirstPhone(): ?string
//    {
//        return $this->customerFirstPhone;
//    }
//    public function setCustomerFirstPhone(?string $customerFirstPhone): void
//    {
//        $this->customerFirstPhone = $customerFirstPhone;
//    }

//    public function getCustomerSecondPhone(): ?string
//    {
//        return $this->customerSecondPhone;
//    }
//    public function setCustomerSecondPhone(?string $customerSecondPhone): void
//    {
//        $this->customerSecondPhone = $customerSecondPhone;
//    }
//
//    public function getCustomerFirstQuestion(): ?string
//    {
//        return $this->customerFirstQuestion;
//    }
//    public function setCustomerFirstQuestion(?string $customerFirstQuestion): void
//    {
//        $this->customerFirstQuestion = $customerFirstQuestion;
//    }
//
//    public function getCustomerFirstQuestionAnswer(): string
//    {
//        return $this->customerFirstQuestionAnswer;
//    }
//    public function setCustomerFirstQuestionAnswer(string $customerFirstQuestionAnswer): void
//    {
//        $this->customerFirstQuestionAnswer = $customerFirstQuestionAnswer;
//    }
//
//    public function getCustomerSecondQuestion(): ?string
//    {
//        return $this->customerSecondQuestion;
//    }
//    public function setCustomerSecondQuestion(?string $customerSecondQuestion): void
//    {
//        $this->customerSecondQuestion = $customerSecondQuestion;
//    }
//
//    public function getCustomerSecondQuestionAnswer(): ?string
//    {
//        return $this->customerSecondQuestionAnswer;
//    }
//    public function setCustomerSecondQuestionAnswer(?string $customerSecondQuestionAnswer): void
//    {
//        $this->customerSecondQuestionAnswer = $customerSecondQuestionAnswer;
//    }
//
//    public function getCustomerSocialApp(): ?CustomerSocialAppEnum
//    {
//        return $this->customerSocialApp;
//    }
//    public function setCustomerSocialApp(?CustomerSocialAppEnum $customerSocialApp): void
//    {
//        $this->customerSocialApp = $customerSocialApp;
//    }
//
//    public function getCustomerSocialAppLink(): ?string
//    {
//        return $this->customerSocialAppLink;
//    }
//    public function setCustomerSocialAppLink(?string $customerSocialAppLink): void
//    {
//        $this->customerSocialAppLink = $customerSocialAppLink;
//    }
//
//    public function getCustomerOkayPassword(): string
//    {
//        return $this->customerOkayPassword;
//    }
//    public function setCustomerOkayPassword(string $customerOkayPassword): void
//    {
//        $this->customerOkayPassword = $customerOkayPassword;
//    }

}

