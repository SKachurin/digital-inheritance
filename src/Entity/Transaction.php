<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Traits\Timestamps;
use App\Repository\TransactionRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TransactionRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Transaction
{
    use Timestamps;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    private ?int $id = 0;

    #[ORM\ManyToOne(targetEntity: Customer::class, inversedBy: 'transactions')]
    #[ORM\JoinColumn(name: 'customer_id', nullable: false)]
    private Customer $customer;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $amount = null;

    #[ORM\Column(type: 'string', length: 64, nullable: true)]
    private ?string $plan = null;

    #[ORM\Column(type: 'string', length: 64, nullable: true)]
    private ?string $currency = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $paymentMethod = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $paidUntil = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $status = null;

    public function __construct(
        Customer $customer
    ) {
        $this->customer = $customer;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getCustomer(): Customer
    {
        return $this->customer;
    }
    public function setCustomer(Customer $customer): void
    {
        $this->customer = $customer;
    }
    public function getAmount(): ?float
    {
        return $this->amount;
    }
    public function setAmount(float $amount): self
    {
        $this->amount = $amount;
        return $this;
    }

    public function getPlan(): ?string
    {
        return $this->plan;
    }

    public function setPlan(string $plan): self
    {
        $this->plan = $plan;
        return $this;
    }
    public function getCurrency(): ?string
    {
        return $this->currency;
    }
    public function setCurrency(string $currency): self
    {
        $this->currency = $currency;
        return $this;
    }
    public function getPaymentMethod(): ?string
    {
        return $this->paymentMethod;
    }
    public function setPaymentMethod(string $paymentMethod): self
    {
        $this->paymentMethod = $paymentMethod;
        return $this;
    }

    public function getPaidUntil(): ?\DateTimeImmutable
    {
        return $this->paidUntil;
    }

    public function setPaidUntil(\DateTimeImmutable $paidUntil): self
    {
        $this->paidUntil = $paidUntil;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }
    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

}