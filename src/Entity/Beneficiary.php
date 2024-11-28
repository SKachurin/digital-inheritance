<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Traits\Timestamps;
use App\Repository\BeneficiaryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: BeneficiaryRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Beneficiary implements UserInterface, PasswordAuthenticatedUserInterface
{
    use Timestamps;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    private ?int $id = 0;

    #[ORM\ManyToOne(targetEntity: Customer::class, inversedBy: 'beneficiaries')]
    #[ORM\JoinColumn(name: 'customer_id', nullable: false)]
    private Customer $customer;

    #[ORM\Column(type: 'string', length: 200, nullable: true)]
    private ?string $beneficiaryName;

    #[ORM\Column(type: 'string', length: 512, nullable: true)]
    private ?string $beneficiaryFullName = null;

    #[ORM\Column(type: 'string', length: 1024, nullable: true)]
    private ?string $beneficiaryFirstQuestion = null;

    #[ORM\Column(type: 'string', length: 512, nullable: true)]
    private ?string $beneficiaryFirstQuestionAnswer = null;

    #[ORM\Column(type: 'string', length: 1024, nullable: true)]
    private ?string $beneficiarySecondQuestion = null;

    #[ORM\Column(type: 'string', length: 512, nullable: true)]
    private ?string $beneficiarySecondQuestionAnswer = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $beneficiaryActionsOrder = null;

    /**
     * @var Collection<int, Note>
     */
    #[ORM\OneToMany(targetEntity: Note::class, mappedBy: 'beneficiary', cascade: ['persist'])]
    private Collection $notes;

    /**
     * @var Collection<int, Contact>
     */
    #[ORM\OneToMany(targetEntity: Contact::class, mappedBy: 'beneficiary', cascade: ['remove'])]
    private Collection $contacts;

    public function __construct(
        ?string $beneficiaryName
    ) {
        $this->beneficiaryName = $beneficiaryName;
        $this->notes = new ArrayCollection();
        $this->contacts = new ArrayCollection();
    }
    public function getId(): int
    {
        return $this->id;
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

    public function getBeneficiaryName(): ?string
    {
        return $this->beneficiaryName;
    }
    public function setBeneficiaryName(string $beneficiaryName): self
    {
        $this->beneficiaryName = $beneficiaryName;
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
            $note->setBeneficiary($this);
        }

        return $this;
    }

    public function addContact(Contact $contact): self
    {
        if (!$this->contacts->contains($contact)) {
            $this->contacts[] = $contact;
            $contact->setBeneficiary($this);
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

    public function removeNote(Note $note): self
    {
        $this->notes->removeElement($note);

        return $this;
    }

    /**
     * @return string[]
     */
    public function getRoles(): array
    {
        // TODO: Implement getRoles() method.
        return[];
    }
    /**
     * @return void
     */
    public function eraseCredentials(): void
    {
        // TODO: Implement eraseCredentials() method.
    }
    /**
     * @return string
     */
    public function getUserIdentifier(): string
    {
        // TODO: Implement getUserIdentifier() method.
        return (string) $this->id;
    }

    /**
     * @return string|null
     */
    public function getPassword(): ?string
    {
        // TODO: Implement getPassword() method.
        return (string) $this->id;
    }
}