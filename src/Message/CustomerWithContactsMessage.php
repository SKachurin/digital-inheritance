<?php
namespace App\Message;

use App\Entity\Contact;
use App\Entity\Customer;

class CustomerWithContactsMessage
{
    private Customer $customer;

    /**
     * @var array<int, Contact>
     */private array $contacts;

    public function __construct(Customer $customer, array $contacts)
    {
        $this->customer = $customer;
        $this->contacts = $contacts;
    }

    public function getCustomer(): Customer
    {
        return $this->customer;
    }

    public function getContacts(): array
    {
        return $this->contacts;
    }
}
