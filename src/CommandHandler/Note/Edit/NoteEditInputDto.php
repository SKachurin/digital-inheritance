<?php

namespace App\CommandHandler\Note\Edit;

use App\Entity\Customer;
use Symfony\Component\Validator\Constraints as Assert;

class NoteEditInputDto
{
    #[Assert\NotBlank]
    private Customer $customer;

    private ?string $customerTextAnswerOne = null;

    private ?string $customerTextAnswerTwo = null;

    private ?string $customerFirstQuestion = null;

    private ?string $customerFirstQuestionAnswer = null;

    private ?string $customerSecondQuestion = null;

    private ?string $customerSecondQuestionAnswer = null;

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

    public function getCustomerTextAnswerOne(): ?string
    {
        return $this->customerTextAnswerOne;
    }

    public function setCustomerTextAnswerOne(?string $customerTextAnswerOne): self
    {
        $this->customerTextAnswerOne = $customerTextAnswerOne;
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

    public function getCustomerFirstQuestion(): ?string
    {
        return $this->customerFirstQuestion;
    }

    public function setCustomerFirstQuestion(?string $customerFirstQuestion): self
    {
        $this->customerFirstQuestion = $customerFirstQuestion;
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

    public function setCustomerSecondQuestionAnswer(?string $customerSecondQuestionAnswer): self
    {
        $this->customerSecondQuestionAnswer = $customerSecondQuestionAnswer;
        return $this;
    }
}
