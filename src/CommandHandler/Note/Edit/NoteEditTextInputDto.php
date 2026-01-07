<?php

namespace App\CommandHandler\Note\Edit;

use App\Entity\Customer;
use App\Entity\Note;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class NoteEditTextInputDto
{
    #[Assert\NotBlank]
    private Customer $customer;

    #[Assert\NotBlank]
    private Note $note;

    // plaintext source (used only by frontend encryption; backend should ignore)
    #[Assert\NotBlank]
    private ?string $customerText;

    private ?int $pipelineId = null;

    // questions + answers (plaintext; backend encrypts questions only)
    #[Assert\NotBlank]
    private ?string $customerFirstQuestion = null;

    #[Assert\NotBlank]
    private ?string $customerFirstQuestionAnswer = null;

    private ?string $customerSecondQuestion = null;
    private ?string $customerSecondQuestionAnswer = null;

    private ?string $beneficiaryFirstQuestion = null;

    #[Assert\NotBlank]
    private ?string $beneficiaryFirstQuestionAnswer = null;

    private ?string $beneficiarySecondQuestion = null;
    private ?string $beneficiarySecondQuestionAnswer = null;

    // frontend encryption flag
    private bool $frontendEncrypted = false;

    // 12 encrypted blobs (JSON strings) – same naming as Note entity fields
    #[Assert\Length(max: 20000)]
    private ?string $customerTextAnswerOne = null;
    #[Assert\Length(max: 20000)]
    private ?string $customerTextAnswerOneKms2 = null;
    #[Assert\Length(max: 20000)]
    private ?string $customerTextAnswerOneKms3 = null;

    #[Assert\Length(max: 20000)]
    private ?string $customerTextAnswerTwo = null;
    #[Assert\Length(max: 20000)]
    private ?string $customerTextAnswerTwoKms2 = null;
    #[Assert\Length(max: 20000)]
    private ?string $customerTextAnswerTwoKms3 = null;

    #[Assert\Length(max: 20000)]
    private ?string $beneficiaryTextAnswerOne = null;
    #[Assert\Length(max: 20000)]
    private ?string $beneficiaryTextAnswerOneKms2 = null;
    #[Assert\Length(max: 20000)]
    private ?string $beneficiaryTextAnswerOneKms3 = null;

    #[Assert\Length(max: 20000)]
    private ?string $beneficiaryTextAnswerTwo = null;
    #[Assert\Length(max: 20000)]
    private ?string $beneficiaryTextAnswerTwoKms2 = null;
    #[Assert\Length(max: 20000)]
    private ?string $beneficiaryTextAnswerTwoKms3 = null;

    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context, $payload): void
    {
        if ($this->customerSecondQuestion && !$this->customerSecondQuestionAnswer) {
            $context->buildViolation('errors.note.edit.second_question_answer_required')
                ->atPath('customerSecondQuestionAnswer')
                ->addViolation();
        }
    }

    public function __construct(Customer $customer, Note $note, ?string $customerText = null)
    {
        $this->customer = $customer;
        $this->note = $note;
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

    public function getNote(): Note
    {
        return $this->note;
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

    // --- 12 encrypted fields getters/setters ---

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
}
