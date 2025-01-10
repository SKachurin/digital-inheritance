<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\ActionTypeEnum;
use App\Enum\IntervalEnum;
use App\Repository\CustomerRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Traits\Timestamps;

#[ORM\Entity(repositoryClass: CustomerRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Action
{
    use Timestamps;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    private ?int $id = 0;

    #[ORM\ManyToOne(targetEntity: Customer::class, inversedBy: 'actions')]
    #[ORM\JoinColumn(name: 'customer_id', nullable: false)]
    private Customer $customer;

    #[ORM\Column(type: 'string', enumType: ActionTypeEnum::class)]
    private ActionTypeEnum $actionType = ActionTypeEnum::SOCIAL_CHECK;

    #[ORM\Column(type: 'string', enumType: IntervalEnum::class)]
    private ?IntervalEnum $timeInterval;

    #[ORM\Column(type: 'string', length: 64, nullable: true)]
    private string $status; // TODO change to enum ActionStatusEnum or delete - it's has no purpose so far

    #[ORM\Column(type: 'string', length: 128, nullable: true)]
    private ?string $chatId;

    #[ORM\ManyToOne(targetEntity: Contact::class)]
    #[ORM\JoinColumn(name: 'contact_id', nullable: true, onDelete: 'CASCADE')]
    private ?Contact $contact = null;


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
    public function getTimeInterval(): ?IntervalEnum
    {
        return $this->timeInterval;
    }
    public function setTimeInterval(IntervalEnum $timeInterval): Action
    {
        $this->timeInterval = $timeInterval;
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

    public function getChatId(): string
    {
        return $this->chatId;
    }
    public function setChatId(string $chatId): Action
    {
        $this->chatId = $chatId;
        return $this;
    }
    public function getContact(): ?Contact
    {
        return $this->contact;
    }
    public function setContact(Contact $contact): Action
    {
        $this->contact = $contact;
        return $this;
    }
}