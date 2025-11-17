<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Traits\Timestamps;
use App\Repository\NoteRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NoteRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Note
{
    use Timestamps;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    private ?int $id = 0; //https://github.com/doctrine/orm/issues/8452

    #[ORM\ManyToOne(targetEntity: Customer::class, inversedBy: 'notes')]
    #[ORM\JoinColumn(name: 'customer_id', nullable: false, onDelete: 'CASCADE')]
    private Customer $customer;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $customerTextAnswerOne = null;

    // replica encrypted with KMS #2
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $customerTextAnswerOneKms2 = null;

    // replica encrypted with KMS #3
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $customerTextAnswerOneKms3 = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $customerTextAnswerTwo = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $customerTextAnswerTwoKms2 = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $customerTextAnswerTwoKms3 = null;

    #[ORM\ManyToOne(targetEntity: Beneficiary::class, inversedBy: 'notes')]
    #[ORM\JoinColumn(name: 'beneficiary_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Beneficiary $beneficiary = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $beneficiaryTextAnswerOne = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $beneficiaryTextAnswerOneKms2 = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $beneficiaryTextAnswerOneKms3 = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $beneficiaryTextAnswerTwo = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $beneficiaryTextAnswerTwoKms2 = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $beneficiaryTextAnswerTwoKms3 = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $attemptCount = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $lastAttemptAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $lockoutUntil = null;

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
    public function setCustomerTextAnswerOne(?string $value): self
    {
        $this->customerTextAnswerOne = $value;
        return $this;
    }
    public function getCustomerTextAnswerOneKms2(): ?string
    {
        return $this->customerTextAnswerOneKms2;
    }
    public function setCustomerTextAnswerOneKms2(?string $value): self
    {
        $this->customerTextAnswerOneKms2 = $value;
        return $this;
    }
    public function getCustomerTextAnswerOneKms3(): ?string
    {
        return $this->customerTextAnswerOneKms3;
    }

    public function setCustomerTextAnswerOneKms3(?string $value): self
    {
        $this->customerTextAnswerOneKms3 = $value;
        return $this;
    }
    public function getCustomerTextAnswerTwo(): ?string
    {
        return $this->customerTextAnswerTwo;
    }
    public function setCustomerTextAnswerTwo(?string $value): self
    {
        $this->customerTextAnswerTwo = $value;
        return $this;
    }
    public function getCustomerTextAnswerTwoKms2(): ?string
    {
        return $this->customerTextAnswerTwoKms2;
    }
    public function setCustomerTextAnswerTwoKms2(?string $value): self
    {
        $this->customerTextAnswerTwoKms2 = $value;
        return $this;
    }
    public function getCustomerTextAnswerTwoKms3(): ?string
    {
        return $this->customerTextAnswerTwoKms3;
    }

    public function setCustomerTextAnswerTwoKms3(?string $value): self
    {
        $this->customerTextAnswerTwoKms3 = $value;
        return $this;
    }
    public function getBeneficiary(): ?Beneficiary
    {
        return $this->beneficiary;
    }
    public function setBeneficiary(?Beneficiary $beneficiary): self
    {
        $this->beneficiary = $beneficiary;
        return $this;
    }
    public function getBeneficiaryTextAnswerOne(): ?string
    {
        return $this->beneficiaryTextAnswerOne;
    }
    public function setBeneficiaryTextAnswerOne(?string $value): self
    {
        $this->beneficiaryTextAnswerOne = $value;
        return $this;
    }
    public function getBeneficiaryTextAnswerOneKms2(): ?string
    {
        return $this->beneficiaryTextAnswerOneKms2;
    }
    public function setBeneficiaryTextAnswerOneKms2(?string $value): self
    {
        $this->beneficiaryTextAnswerOneKms2 = $value;
        return $this;
    }
    public function getBeneficiaryTextAnswerOneKms3(): ?string
    {
        return $this->beneficiaryTextAnswerOneKms3;
    }
    public function setBeneficiaryTextAnswerOneKms3(?string $value): self
    {
        $this->beneficiaryTextAnswerOneKms3 = $value;
        return $this;
    }

    public function getBeneficiaryTextAnswerTwo(): ?string
    {
        return $this->beneficiaryTextAnswerTwo;
    }

    public function setBeneficiaryTextAnswerTwo(?string $value): self
    {
        $this->beneficiaryTextAnswerTwo = $value;
        return $this;
    }
    public function getBeneficiaryTextAnswerTwoKms2(): ?string
    {
        return $this->beneficiaryTextAnswerTwoKms2;
    }
    public function setBeneficiaryTextAnswerTwoKms2(?string $value): self
    {
        $this->beneficiaryTextAnswerTwoKms2 = $value;
        return $this;
    }
    public function getBeneficiaryTextAnswerTwoKms3(): ?string
    {
        return $this->beneficiaryTextAnswerTwoKms3;
    }
    public function setBeneficiaryTextAnswerTwoKms3(?string $value): self
    {
        $this->beneficiaryTextAnswerTwoKms3 = $value;
        return $this;
    }
    public function getAttemptCount(): ?int
    {
        return $this->attemptCount;
    }
    public function setAttemptCount(?int $attemptCount): self
    {
        $this->attemptCount = $attemptCount;
        return $this;
    }

    public function setLastAttemptAtValue(): self
    {
        $this->lastAttemptAt = new \DateTimeImmutable();
        return $this;
    }
    public function getLastAttemptAt(): ?\DateTimeImmutable
    {
        return $this->lastAttemptAt;
    }

    public function setLockoutUntil(?\DateTimeImmutable $dateTime): self
    {
        $this->lockoutUntil = $dateTime;
        return $this;
    }

    public function getLockoutUntil(): ?\DateTimeImmutable
    {
        return $this->lockoutUntil;
    }
}