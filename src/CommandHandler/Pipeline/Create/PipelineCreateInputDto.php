<?php

namespace App\CommandHandler\Pipeline\Create;

use App\Entity\Customer;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class PipelineCreateInputDto
{
    #[Assert\NotBlank]
    private Customer $customer;

    private ?string $customerOkayPassword;

    /**
     * @var ActionDto[]
     *
     * @Assert\Valid
     */
    private array $actions = [];

    #[Assert\Callback]
    public function validateUniquePositions(ExecutionContextInterface $context): void
    {
        $positions = [];
        foreach ($this->actions as $key => $action) {
            $position = $action->getPosition();
            if ($position !== null) {
                if (in_array($position, $positions)) {
                    // Duplicate found
                    $context->buildViolation('This position is already used. Actions can\'t fire at the same time')
                        ->atPath("actions[$key].position")
                        ->addViolation();
                } else {
                    $positions[] = $position;
                }
            }
        }
    }

    public function __construct(?string  $customerOkayPassword, Customer $customer = null)
    {
        $this->customer = $customer;
        $this->customerOkayPassword = $customerOkayPassword;
    }

    public function getCustomerOkayPassword(): string
    {
        return $this->customerOkayPassword;
    }
    public function setCustomerOkayPassword(string $customerOkayPassword): void
    {
        $this->customerOkayPassword = $customerOkayPassword;
    }

    public function getCustomer(): Customer
    {
        return $this->customer;
    }

    /**
     * @return ActionDto[]
     */
    public function getActions(): array
    {
        return $this->actions;
    }

    /**
     * @param ActionDto[] $actions
     */
    public function setActions(array $actions): self
    {
        $this->actions = $actions;
        return $this;
    }
}
