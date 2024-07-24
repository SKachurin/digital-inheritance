<?php

declare(strict_types=1);

namespace App\Validator\Customer;

use App\Repository\CustomerRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class CustomerValidator extends ConstraintValidator
{
    public function __construct(
        private CustomerRepository $customerRepository,
    ) {}

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof Customer) {
            throw new UnexpectedTypeException($constraint, Customer::class);
        }

        // ignore null and empty values
        if (null === $value || '' === $value) {
            return;
        }
        $customer = $this->customerRepository->findOneBy(['id' => $value]);

        if (!$customer) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ customer }}', 'Customer')
                ->addViolation()
            ;
        }
    }
}
