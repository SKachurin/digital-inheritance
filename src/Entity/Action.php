<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\ActionTypeEnum;
use App\Enum\CustomerSocialAppEnum;
use App\Enum\CustomerPaymentStatuseEnum;
use App\Enum\IntervalEnum;
use App\Repository\CustomerRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\ManyToOne;
use App\Entity\Traits\Timestamps;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV4;
use Symfony\Component\Validator\Constraints\NotBlank;

#[ORM\Entity(repositoryClass: CustomerRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Action
{
    use Timestamps;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    private int $id = 0;

    #[ORM\ManyToOne(targetEntity: Customer::class, inversedBy: 'actions')]
    #[ORM\JoinColumn(name: 'customer_id', nullable: false)]
    private Customer $customer;

    #[ORM\Column(type: 'string', enumType: ActionTypeEnum::class)]
    private ActionTypeEnum $actionType = ActionTypeEnum::SOCIAL_CHECK;

    #[ORM\Column(type: 'string', enumType: IntervalEnum::class)]
    private ?IntervalEnum $interval;

    #[ORM\Column(type: 'string', length: 64, nullable: true)]
    private string $status;

    #[ORM\ManyToOne(targetEntity: Note::class, inversedBy: 'actions')]
    #[ORM\JoinColumn(name: 'note_id', nullable: false)]
    private ?Note $note;

    public function __construct(
        Customer $customer,
        ActionTypeEnum $actionType
    ) {
        $this->customer = $customer;
        $this->actionType = $actionType;
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

    public function getActionType(): ActionTypeEnum
    {
        return $this->actionType;
    }
    public function getInterval(): ?IntervalEnum
    {
        return $this->interval;
    }
    public function setInterval(IntervalEnum $interval): Action
    {
        $this->interval = $interval;
        return $this;
    }
    public function getStatus(): string
    {
        return $this->status;
    }
    public function setStatus(string $status): Action
    {
        $this->status = $status;
        return $this;
    }
    public function getNote(): ?Note
    {
        return $this->note;
    }
    public function setNote(Note $note): Action
    {
        $this->note = $note;
        return $this;
    }
}