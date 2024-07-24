<?php

declare(strict_types=1);

namespace App\Validator\Customer;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class Customer extends Constraint
{
    public string $message = 'Undefined "customer". ';
}
