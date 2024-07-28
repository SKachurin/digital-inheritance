<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Traits\Timestamps;
use App\Repository\NoteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: NoteRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Note
{
    use Timestamps;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    private int $id = 0;

    #[ORM\ManyToOne(targetEntity: Customer::class, inversedBy: 'notes')]
    #[ORM\JoinColumn(name: 'customer_id', nullable: false)]
    private Customer $customer;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $customerTextAnswerOne = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $customerTextAnswerTwo = null;

    #[ORM\ManyToOne(targetEntity: Beneficiary::class, inversedBy: 'notes')]
    #[ORM\JoinColumn(name: 'beneficiary_id', nullable: true)]
    private ?Beneficiary $beneficiary = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $beneficiaryTextAnswerOne = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $beneficiaryTextAnswerTwo = null;

    #[ORM\OneToOne(targetEntity: Pipeline::class, mappedBy: 'note')]
    private ?Pipeline $pipeline = null;

    /**
     * @var Collection<int, Action>
     */
    #[ORM\OneToMany(targetEntity: Action::class, mappedBy: 'note', cascade: ['persist'])]
    private Collection $actions;

    public function __construct(

    ) {
        $this->actions = new ArrayCollection();
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
    public function getBeneficiary(): ?Beneficiary
    {
        return $this->beneficiary;
    }
    public function setBeneficiary(Beneficiary $beneficiary): self
    {
        $this->beneficiary = $beneficiary;
        return $this;
    }
    public function getBeneficiaryTextAnswerOne(): ?string
    {
        return $this->beneficiaryTextAnswerOne;
    }
    public function setBeneficiaryTextAnswerOne(string $beneficiaryTextAnswerOne): self
    {
        $this->beneficiaryTextAnswerOne = $beneficiaryTextAnswerOne;
        return $this;
    }
    public function getBeneficiaryTextAnswerTwo(): ?string
    {
        return $this->beneficiaryTextAnswerTwo;
    }
    public function setBeneficiaryTextAnswerTwo(string $beneficiaryTextAnswerTwo): self
    {
        $this->beneficiaryTextAnswerTwo = $beneficiaryTextAnswerTwo;
        return $this;
    }

    public function getPipeline(): ?Pipeline
    {
        return $this->pipeline;
    }
    public function setPipeline(Pipeline $pipeline): self
    {
        $this->pipeline = $pipeline;
        return $this;
    }

    public function addAction(Action $action): self
    {
        if (!$this->actions->contains($action)) {
            $this->actions[] = $action;
            $action->setNote($this);
        }

        return $this;
    }

    public function removeAction(Action $action): self
    {
        if ($this->actions->removeElement($action)) {
            // set the owning side to null (unless already changed)
            if ($action->getNote() === $this) {
                $action->setNote($this);
            }
        }

        return $this;
    }
}