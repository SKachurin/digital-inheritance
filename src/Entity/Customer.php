<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\CustomerSocialAppEnum;
use App\Enum\CustomerPaymentStatusEnum;
use App\Repository\CustomerRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use App\Entity\Traits\Timestamps;

#[ORM\Entity(repositoryClass: CustomerRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Customer implements UserInterface, PasswordAuthenticatedUserInterface
{
    use Timestamps;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    private ?int $id = 0; //https://github.com/doctrine/orm/issues/8452

    #[ORM\Column(type: 'string', length: 64, nullable: true)]
    private string $customerName;

    #[Assert\Email]
    #[ORM\Column(type: 'string', length: 64, unique: true)] //not encoding it for now
    private string $customerEmail;

    #[ORM\Column(type: 'string', length: 512, nullable: true)]
    private ?string $customerFullName = null;

    #[ORM\Column(type: 'string', length: 1024, nullable: true)]
    private ?string $customerFirstQuestion = null;

    #[ORM\Column(type: 'string', length: 512, nullable: true)]
    private ?string $customerFirstQuestionAnswer = null;

    #[ORM\Column(type: 'string', length: 1024, nullable: true)]
    private ?string $customerSecondQuestion = null;

    #[ORM\Column(type: 'string', length: 512, nullable: true)]
    private ?string $customerSecondQuestionAnswer = null;

    #[ORM\Column(type: 'string', enumType: CustomerSocialAppEnum::class)]
    private CustomerSocialAppEnum $customerSocialApp = CustomerSocialAppEnum::NONE;

    #[ORM\Column(type: 'string', enumType: CustomerPaymentStatusEnum::class)]
    private CustomerPaymentStatusEnum $customerPaymentStatus;

    #[ORM\Column(type: 'string', length: 512, nullable: true)]
    private ?string $customerOkayPassword = null;

    #[ORM\Column(type: "string", length: 5, nullable: true)]
    private ?string $lang = null;

    /**
     * @var string The hashed password
     */
    #[ORM\Column(type: 'string', length: 512, nullable: true)]
    private string $password;

    /**
     * @var Collection<int, Note>
     */
    #[ORM\OneToMany(targetEntity: Note::class, mappedBy: 'customer', cascade: ['remove'], orphanRemoval: true)]
    private Collection $notes;

    /**
     * @var Collection<int, Beneficiary>
     */
    #[ORM\OneToMany(targetEntity: Beneficiary::class, mappedBy: 'customer', cascade: ['remove'], orphanRemoval: true)]
    private Collection $beneficiaries;

    /**
     * @var Collection<int, Transaction>
     */
    #[ORM\OneToMany(targetEntity: Transaction::class, mappedBy: 'customer')]
    private Collection $transactions;

    /**
     * @var Collection<int, Pipeline>
     */
    #[ORM\OneToMany(targetEntity: Pipeline::class, mappedBy: 'customer', cascade: ['remove'], orphanRemoval: true)]
    private Collection $pipelines;

    /**
     * @var Collection<int, Action>
     */
    #[ORM\OneToMany(targetEntity: Action::class, mappedBy: 'customer', cascade: ['remove'], orphanRemoval: true)]
    private Collection $actions;

    #[ORM\Column(type: 'json')]
    private array $roles = [];

    /**
     * @var Collection<int, Contact>
     */
    #[ORM\OneToMany(targetEntity: Contact::class, mappedBy: 'customer', cascade: ['remove'])]
    private Collection $contacts;
//
//    #[ORM\Column(type: 'string', length: 32, nullable: true)]
//    private ?string $referralId = null;
//
//    #[ORM\Column(type: 'string', length: 32, nullable: true)]
//    private ?string $invitedBy = null;

    public function __construct()
    {
        $this->customerPaymentStatus = CustomerPaymentStatusEnum::NOT_PAID;
        $this->notes = new ArrayCollection();
        $this->transactions = new ArrayCollection();
        $this->beneficiaries = new ArrayCollection();
        $this->pipelines = new ArrayCollection();
        $this->actions = new ArrayCollection();
        $this->contacts = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getCustomerName(): string
    {
        return $this->customerName;
    }

    public function setCustomerName(string $customerName): Customer
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


    public function getCustomerFullName(): ?string
    {
        return $this->customerFullName;
    }

    public function setCustomerFullName(?string $customerFullName): self
    {
        $this->customerFullName = $customerFullName;
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

    public function getCustomerSocialApp(): CustomerSocialAppEnum
    {
        return $this->customerSocialApp;
    }

    public function setCustomerSocialApp(?CustomerSocialAppEnum $customerSocialApp): self
    {
        $this->customerSocialApp = $customerSocialApp ?? CustomerSocialAppEnum::NONE;
        return $this;
    }

    public function getCustomerOkayPassword(): ?string
    {
        return $this->customerOkayPassword;
    }

    public function setCustomerOkayPassword(?string $customerOkayPassword): self
    {
        $this->customerOkayPassword = $customerOkayPassword;
        return $this;
    }

    public function getLang(): ?string
    {
        return $this->lang;
    }

    public function setLang(?string $lang): self
    {
        $this->lang = $lang;
        return $this;
    }

    public function getCustomerPaymentStatus(): CustomerPaymentStatusEnum
    {
        return $this->customerPaymentStatus;
    }

    public function setCustomerPaymentStatus(CustomerPaymentStatusEnum $customerPaymentStatus): self
    {
        $this->customerPaymentStatus = $customerPaymentStatus;
        return $this;
    }

    /**
     * @return Collection<int, Note>
     */
    public function getNotes(): Collection
    {
        return $this->notes;
    }

    public function addNote(Note $note): self
    {
        if (!$this->notes->contains($note)) {
            $this->notes->add($note);
            $note->setCustomer($this);
        }

        return $this;
    }

    public function removeNote(Note $note): self
    {
        $this->notes->removeElement($note);

        return $this;
    }

    /**
     * @return Collection<int, Beneficiary>
     */
    public function getBeneficiary(): Collection
    {
        return $this->beneficiaries;
    }

    public function addBeneficiary(Beneficiary $beneficiary): self
    {
        if (!$this->beneficiaries->contains($beneficiary)) {
            $this->beneficiaries->add($beneficiary);
            $beneficiary->setCustomer($this);
        }

        return $this;
    }

    public function removeBeneficiary(Beneficiary $beneficiary): self
    {
        $this->beneficiaries->removeElement($beneficiary);

        return $this;
    }

    /**
     * @return Collection<int, Transaction>
     */
    public function getTransactions(): Collection
    {
        return $this->transactions;
    }

    public function addTransaction(Transaction $transaction): self
    {
        if (!$this->transactions->contains($transaction)) {
            $this->transactions->add($transaction);
            $transaction->setCustomer($this);
        }

        return $this;
    }

    public function removeTransaction(Transaction $transaction): self
    {
        $this->transactions->removeElement($transaction);

        return $this;
    }

    /**
     * @return Collection<int, Pipeline>
     */
    public function getPipelines(): Collection
    {
        return $this->pipelines;
    }

    public function addPipeline(Pipeline $pipeline): self
    {
        if (!$this->pipelines->contains($pipeline)) {
            $this->pipelines->add($pipeline);
            $pipeline->setCustomer($this);
        }

        return $this;
    }

    public function removePipeline(Pipeline $pipeline): self
    {
        $this->pipelines->removeElement($pipeline);

        return $this;
    }

    /**
     * @return Collection<int, Action>
     */
    public function getActions(): Collection
    {
        return $this->actions;
    }

    public function addAction(Action $action): self
    {
        if (!$this->actions->contains($action)) {
            $this->actions->add($action);
            $action->setCustomer($this);
        }

        return $this;
    }

    public function removeAction(Action $action): self
    {
        $this->actions->removeElement($action);

        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string)$this->customerEmail;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }


    public function addContact(Contact $contact): self
    {
        if (!$this->contacts->contains($contact)) {
            $this->contacts[] = $contact;
            $contact->setCustomer($this);
        }

        return $this;
    }

    public function removeContact(Contact $contact): self
    {
        if ($this->contacts->contains($contact)) {
            $this->contacts->removeElement($contact);
        }

        return $this;
    }

    /**
     * @return Collection<int, Contact>
     */
    public function getContacts(): Collection
    {
        return $this->contacts;
    }
//
//    public function getReferralId(): ?string
//    {
//        return $this->referralId;
//    }
//
//    public function setReferralId(?string $referralId): self
//    {
//        $this->referralId = $referralId;
//        return $this;
//    }
//
//    public function getInvitedBy(): ?string
//    {
//        return $this->invitedBy;
//    }
//
//    public function setInvitedBy(?string $invitedBy): self
//    {
//        $this->invitedBy = $invitedBy;
//        return $this;
//    }


    public function isTrialActive(): bool
    {
        $trialEnd = (clone $this->getCreatedAt())->modify('+3 days');
        return new \DateTimeImmutable() < $trialEnd;
    }

    public function getTrialEndDate(): \DateTimeImmutable
    {
        return (clone $this->getCreatedAt())->modify('+3 days');
    }

    public function eraseCredentials(): void
    {
        // Implement eraseCredentials() method.
    }

}