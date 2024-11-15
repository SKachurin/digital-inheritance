<?php

declare(strict_types=1);

namespace App\CommandHandler\Note\Delete;

use App\Entity\Customer;
use Symfony\Component\Validator\Constraints as Assert;

class NoteDeleteInputDto
{
    #[Assert\NotBlank]
    private Customer $customer;

    #[Assert\NotBlank]
    #[Assert\Type('integer')]
    private int $noteId;

    public function __construct(Customer $customer, int $noteId)
    {
        $this->customer = $customer;
        $this->noteId = $noteId;
    }

    public function getCustomer(): Customer
    {
        return $this->customer;
    }

    public function getNoteId(): int
    {
        return $this->noteId;
    }

    public function setNoteId(int $noteId): self
    {
        $this->noteId = $noteId;
        return $this;
    }
}
