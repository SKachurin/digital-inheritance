<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Traits\Timestamps;
use App\Repository\PipelineRepository;
use Doctrine\Common\Collections\Collection;
use App\Enum\ActionStatusEnum;
use App\Enum\ActionTypeEnum;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PipelineRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Pipeline
{
    use Timestamps;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    private int $id = 0;

    #[ORM\ManyToOne(targetEntity: Customer::class, inversedBy: 'pipelines')]
    #[ORM\JoinColumn(name: 'customer_id', nullable: false)]
    private ?Customer $customer;

    #[ORM\Column(type: 'string', enumType: ActionStatusEnum::class)]
    private ?ActionStatusEnum $actionStatus;

    #[ORM\Column(type: 'string', enumType: ActionTypeEnum::class)]
    private ?ActionTypeEnum $actionType = ActionTypeEnum::SOCIAL_CHECK;

    //TODO ActionStatusEnum to PipelineStatusEnum
    #[ORM\Column(type: 'string', enumType: ActionStatusEnum::class)]
    private ActionStatusEnum $pipelineStatus;

    #[ORM\Column(type: 'json')]
    private array $actionSequence = [];

    public function __construct(
        Customer $customer,
        ActionStatusEnum $pipelineStatus,
        array $actionSequence = []
    ) {
        $this->customer = $customer;
        $this->pipelineStatus = $pipelineStatus;
        $this->actionSequence = $actionSequence;
    }

    public function getId(): int
    {
        return $this->id;
    }
    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }
    public function setCustomer(Customer $customer): self
    {
        $this->customer = $customer;
        return $this;
    }
    public function getActionStatus(): ?ActionStatusEnum
    {
        return $this->actionStatus;
    }
    public function setActionStatus(ActionStatusEnum $actionStatus): self
    {
        $this->actionStatus = $actionStatus;
        return $this;
    }

    public function getPipelineStatus(): ?ActionStatusEnum
    {
        return $this->pipelineStatus;
    }
    public function setPipelineStatus(ActionStatusEnum $pipelineStatus): self
    {
        $this->pipelineStatus = $pipelineStatus;
        return $this;
    }
    public function getActionType(): ?ActionTypeEnum
    {
        return $this->actionType;
    }
    public function setActionType(ActionTypeEnum $actionType): self
    {
        $this->actionType = $actionType;
        return $this;
    }

    public function getActionSequence(): array
    {
        return $this->actionSequence;
    }

    public function setActionSequence(array $actionSequence): self
    {
        $this->actionSequence = $actionSequence;
        return $this;
    }

}