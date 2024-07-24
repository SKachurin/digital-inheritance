<?php

declare(strict_types=1);

namespace App\CommandHandler\Customer\Create;

use App\Entity\Customer;
use App\Enum\CustomerPaymentStatusEnum;
use App\Enum\CustomerSocialAppEnum;
use Doctrine\Common\Collections\Collection;

class CustomerCreateOutputDto implements \JsonSerializable
{
    private string $customerName;
    private string $customerEmail;
//    private ?string $customerSecondEmail;
    private ?string $customerFullName;
//    private ?string $customerCountryCode;
//    private ?string $customerFirstPhone;
//    private ?string $customerSecondPhone;
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
        Customer $customer
    ) {
        $this->customerEmail = $customer->getCustomerEmail();
        $this->customerName = $customer->getCustomerName();
//        $this->customerSecondEmail = (string) $customer->getCustomerSecondEmail();
        $this->customerFullName = (string) $customer->getCustomerFullName();
//        $this->customerCountryCode = (string) $customer->getCustomerCountryCode();
//        $this->customerFirstPhone = (string) $customer->getCustomerFirstPhone();
//        $this->customerSecondPhone = (string) $customer->getCustomerSecondPhone();
        $this->customerFirstQuestion = (string) $customer->getCustomerFirstQuestion();
        $this->customerFirstQuestionAnswer = (string) $customer->getCustomerFirstQuestionAnswer();
        $this->customerSecondQuestion = (string) $customer->getCustomerSecondQuestion();
        $this->customerSecondQuestionAnswer = (string) $customer->getCustomerSecondQuestionAnswer();
        $this->customerSocialApp = $customer->getCustomerSocialApp();
        $this->customerPaymentStatus = $customer->getCustomerPaymentStatus();
        $this->customerOkayPassword = $customer->getCustomerOkayPassword();
        $this->password = $customer->getPassword();
        $this->customerActionsOrder = (string) $customer->getCustomerActionsOrder();
        $this->createdAt = $customer->getCreatedAt()->format('Y-m-d H:i:s');

    }

   public function getCustomerName(): string
   {
       return $this->customerName;
   }
   public function getCustomerEmail(): string
   {
       return $this->customerEmail;
   }
//   public function getCustomerSecondEmail(): ?string
//   {
//       return $this->customerSecondEmail;
//   }
   public function getCustomerFullName(): ?string
   {
       return $this->customerFullName;
   }
//   public function getCustomerCountryCode(): ?string
//   {
//       return $this->customerCountryCode;
//   }
//   public function getCustomerFirstPhone(): ?string
//   {
//       return $this->customerFirstPhone;
//   }
//   public function getCustomerSecondPhone(): ?string
//   {
//       return $this->customerSecondPhone;
//   }
   public function getCustomerFirstQuestion(): ?string
   {
       return $this->customerFirstQuestion;
   }
   public function getCustomerFirstQuestionAnswer(): ?string
   {
       return $this->customerFirstQuestionAnswer;
   }
   public function getCustomerSecondQuestion(): ?string
   {
       return $this->customerSecondQuestion;
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
