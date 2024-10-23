<?php

namespace App\CommandHandler\Pipeline\Create;

use App\Enum\ActionTypeEnum;
use App\Enum\IntervalEnum;
use Symfony\Component\Validator\Constraints as Assert;

class ActionDto
{
    #[Assert\NotBlank]
    #[Assert\Positive]
    private ?int $position = null;

    private ?ActionTypeEnum $actionType = null;

    private ?IntervalEnum $interval = null;

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(?int $position): self
    {
        $this->position = $position;
        return $this;
    }

    public function getActionType(): ?ActionTypeEnum
    {
        return $this->actionType;
    }

    public function setActionType(?ActionTypeEnum $actionType): self
    {
        $this->actionType = $actionType;
        return $this;
    }

    public function getInterval(): ?IntervalEnum
    {
        return $this->interval;
    }

    public function setInterval(?IntervalEnum $interval): self
    {
        $this->interval = $interval;
        return $this;
    }
}
