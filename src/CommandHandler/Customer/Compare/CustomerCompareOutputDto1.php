<?php

declare(strict_types=1);

namespace App\CommandHandler\Customer\Compare;

use App\Entity\Customer;
use App\Enum\CustomerPaymentStatusEnum;
use App\Enum\CustomerSocialAppEnum;

class CustomerCompareOutputDto1 implements \JsonSerializable
{
    private string $customerName;
    private string $customerEmail;
    private ?string $customerSecondEmail;
    private ?string $customerFullName;
    private ?string $customerCountryCode;
    private ?string $customerFirstPhone;
    private ?string $customerSecondPhone;
    private ?string $customerFirstQuestion;
    private ?string $customerFirstQuestionAnswer;
    private ?string $customerSecondQuestion;
    private ?string $customerSecondQuestionAnswer;
    private ?CustomerSocialAppEnum $customerSocialApp;
    private CustomerPaymentStatusEnum $customerPaymentStatus;
    private string $customerOkayPassword;
    private string $password;
    private ?string $customerActionsOrder;
    private string $createdAt;

    public function __construct(
        Customer $customer,

    ) {
        $this->customerEmail = $customer->getCustomerEmail();
        $this->customerName = $customer->getCustomerName();
        $this->customerSecondEmail = null;  // Will be set based on contacts
        $this->customerFullName = $customer->getCustomerFullName();
        $this->customerCountryCode = null;  // Will be set based on contacts
        $this->customerFirstPhone = null;  // Will be set based on contacts
        $this->customerSecondPhone = null;  // Will be set based on contacts
        $this->customerFirstQuestion = $customer->getCustomerFirstQuestion();
        $this->customerFirstQuestionAnswer = $customer->getCustomerFirstQuestionAnswer();
        $this->customerSecondQuestion = $customer->getCustomerSecondQuestion();
        $this->customerSecondQuestionAnswer = $customer->getCustomerSecondQuestionAnswer();
        $this->customerSocialApp = $customer->getCustomerSocialApp();
        $this->customerPaymentStatus = $customer->getCustomerPaymentStatus();
        $this->customerOkayPassword = $customer->getCustomerOkayPassword();
        $this->password = $customer->getPassword();
        $this->customerActionsOrder = $customer->getCustomerActionsOrder();
        $this->createdAt = $customer->getCreatedAt()->format('Y-m-d H:i:s');

    }

   public function getCustomerName(): string
   {
       return $this->customerName;
   }
   public function setCustomerName(string $customerName): self
   {
       $this->customerName = $customerName;
       return $this;
   }
   public function getCustomerEmail(): string
   {
       return $this->customerEmail;
   }
   public function setCustomerEmail(string $customerEmail): self
   {
       $this->customerEmail = $customerEmail;
       return $this;
   }
   public function getCustomerSecondEmail(): ?string
   {
       return $this->customerSecondEmail;
   }
   public function setCustomerSecondEmail(?string $customerSecondEmail): self
   {
       $this->customerSecondEmail = $customerSecondEmail;
       return $this;
   }
   public function getCustomerFullName(): ?string
   {
       return $this->customerFullName;
   }
   public function setCustomerFullName(?string $customerFullName): self
   {
       $this->customerFullName = $customerFullName;
       return $this;
   }
   public function getCustomerCountryCode(): ?string
   {
       return $this->customerCountryCode;
   }
   public function setCustomerCountryCode(?string $customerCountryCode): self
   {
       $this->customerCountryCode = $customerCountryCode;
       return $this;
   }
   public function setCustomerFirstPhone(?string $customerFirstPhone): self
   {
       $this->customerFirstPhone = $customerFirstPhone;
       return $this;
   }
   public function getCustomerFirstPhone(): ?string
   {
       return $this->customerFirstPhone;
   }
   public function setCustomerSecondPhone(?string $customerSecondPhone): self
   {
       $this->customerSecondPhone = $customerSecondPhone;
       return $this;
   }
   public function getCustomerSecondPhone(): ?string
   {
       return $this->customerSecondPhone;
   }
   public function setCustomerFirstQuestion(?string $customerFirstQuestion): self
   {
       $this->customerFirstQuestion = $customerFirstQuestion;
       return $this;
   }
   public function getCustomerFirstQuestion(): ?string
   {
       return $this->customerFirstQuestion;
   }
   public function setCustomerFirstQuestionAnswer(?string $customerFirstQuestionAnswer): self
   {
       $this->customerFirstQuestionAnswer = $customerFirstQuestionAnswer;
       return $this;
   }
   public function getCustomerFirstQuestionAnswer(): ?string
   {
       return $this->customerFirstQuestionAnswer;
   }
   public function setCustomerSecondQuestionAnswer(?string $customerSecondQuestionAnswer): self
   {
       $this->customerSecondQuestionAnswer = $customerSecondQuestionAnswer;
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
   public function getCustomerSecondQuestionAnswer(): ?string
   {
       return $this->customerSecondQuestionAnswer;
   }
   public function getCustomerSocialApp(): ?CustomerSocialAppEnum
   {
       return $this->customerSocialApp;
   }
   public function getCustomerPaymentStatus(): CustomerPaymentStatusEnum
   {
       return $this->customerPaymentStatus;
   }
   public function getCustomerOkayPassword(): string
   {
       return $this->customerOkayPassword;
   }
   public function getPassword(): string
   {
        return $this->password;
   }
   public function getCustomerActionsOrder(): ?string
   {
       return $this->customerActionsOrder;
   }
   public function getCreatedAt(): string
   {
       return $this->createdAt;
   }
    public function jsonSerialize(): mixed
    {
        return get_object_vars($this);
    }
}
