<?php

namespace App\CommandHandler\Note\Create;

use App\Entity\Customer;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class NoteCreateInputDto
{
    #[Assert\NotBlank]
    private Customer $customer;

    private bool $frontendEncrypted = false;

    #[Assert\NotBlank]
    private ?string $customerText;

    private ?int $pipelineId;

    #[Assert\NotBlank]
    private ?string $customerFirstQuestion = null;

    #[Assert\NotBlank]
    private ?string $customerFirstQuestionAnswer = null;

    private ?string $customerSecondQuestion = null;

    private ?string $customerSecondQuestionAnswer = null;

    #[Assert\NotBlank]
    private ?string $beneficiaryFirstQuestion = null;

    #[Assert\NotBlank]
    private ?string $beneficiaryFirstQuestionAnswer = null;

    private ?string $beneficiarySecondQuestion = null;

    private ?string $beneficiarySecondQuestionAnswer = null;

    #[Assert\Length(max: 10000)]
    private ?string $customerTextAnswerOne = null;
    private ?string $customerTextAnswerOneKms2 = null;
    private ?string $customerTextAnswerOneKms3 = null;

    #[Assert\Length(max: 10000)]
    private ?string $customerTextAnswerTwo = null;
    private ?string $customerTextAnswerTwoKms2 = null;
    private ?string $customerTextAnswerTwoKms3 = null;

    private ?int $beneficiaryId;

    #[Assert\Length(max: 10000)]
    private ?string $beneficiaryTextAnswerOne = null;
    private ?string $beneficiaryTextAnswerOneKms2 = null;
    private ?string $beneficiaryTextAnswerOneKms3 = null;

    #[Assert\Length(max: 10000)]
    private ?string $beneficiaryTextAnswerTwo = null;
    private ?string $beneficiaryTextAnswerTwoKms2 = null;
    private ?string $beneficiaryTextAnswerTwoKms3 = null;

    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context, $payload): void
    {
        if ($this->customerSecondQuestion && !$this->customerSecondQuestionAnswer) {
            $context->buildViolation('Answer to the second question is required.')
                ->atPath('customerSecondQuestionAnswer')
                ->addViolation();
        }
    }

    public function __construct(
        Customer $customer,
        ?string $customerText = null,

    ) {
        $this->customer = $customer;
        $this->customerText = $customerText;
    }

    public function isFrontendEncrypted(): bool
    {
        return $this->frontendEncrypted;
    }

    public function setFrontendEncrypted(bool|string|int|null $v): self
    {
        $this->frontendEncrypted = ($v === true || $v === 1 || $v === '1');
        return $this;
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

    public function getPipelineId(): ?int
    {
        return $this->pipelineId;
    }

    public function setPipelineId(int $pipelineId): self
    {
        $this->pipelineId = $pipelineId;
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

    public function getCustomerTextAnswerTwo(): ?string
    {
        return $this->customerTextAnswerTwo;
    }

    public function setCustomerTextAnswerTwo(?string $customerTextAnswerTwo): self
    {
        $this->customerTextAnswerTwo = $customerTextAnswerTwo;
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

    public function getBeneficiaryId(): ?int
    {
        return $this->beneficiaryId;
    }

    public function setBeneficiaryId(?int $beneficiaryId): self
    {
        $this->beneficiaryId = $beneficiaryId;
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

    public function getBeneficiaryTextAnswerTwo(): ?string
    {
        return $this->beneficiaryTextAnswerTwo;
    }

    public function setBeneficiaryTextAnswerTwo(?string $beneficiaryTextAnswerTwo): self
    {
        $this->beneficiaryTextAnswerTwo = $beneficiaryTextAnswerTwo;
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
}
