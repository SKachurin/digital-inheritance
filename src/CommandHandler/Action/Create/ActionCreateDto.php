<?php
namespace App\CommandHandler\Action\Create;

namespace App\CommandHandler\Action\Create;

use App\Entity\Contact;
use App\Entity\Customer;
use App\Enum\ActionTypeEnum;
use App\Enum\IntervalEnum;
use App\Entity\Note;

class ActionCreateDto
{
    private Contact $contact;
    private ActionTypeEnum $actionType;
    private ?IntervalEnum $interval = null;
    private string $status = '';
    private ?Note $note = null;

    public function __construct(
        Contact $contact,
        ActionTypeEnum $actionType,
        ?IntervalEnum $interval = null,
        string $status = ''
    ) {
        $this->contact = $contact;
        $this->actionType = $actionType;
        $this->interval = $interval;
        $this->status = $status;
    }

    public function getContact(): Contact
    {
        return $this->contact;
    }

    public function getCustomer(): ?Customer
    {
        return $this->contact->getCustomer(); // Get customer from the contact
    }

    public function getActionType(): ActionTypeEnum
    {
        return $this->actionType;
    }

    public function getInterval(): ?IntervalEnum
    {
        return $this->interval;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    // Setters if needed
    public function setContact(Contact $contact): self
    {
        $this->contact = $contact;
        return $this;
    }

    public function setActionType(ActionTypeEnum $actionType): self
    {
        $this->actionType = $actionType;
        return $this;
    }

    public function setInterval(?IntervalEnum $interval): self
    {
        $this->interval = $interval;
        return $this;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }
}
