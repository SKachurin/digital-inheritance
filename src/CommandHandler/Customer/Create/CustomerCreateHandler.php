<?php

declare(strict_types=1);

namespace App\CommandHandler\Customer\Create;

use App\Queue\Doctrine\Customer\CustomerCreatedProducer;
use App\Repository\CustomerRepository;
use App\Queue\Doctrine\Customer\CustomerCreatedMessage;
use Doctrine\ORM\Query\QueryException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class CustomerCreateHandler
{
    public function __construct(
        private CustomerRepository $customerRepository,
        private CustomerCreatedProducer $customerCreatedProducer,
    ) {}

    /**
     * @throws QueryException
     */
    public function __invoke(CustomerCreateInputDto $input): void //CustomerCreateOutputDto
    {
        $isDouble = $this->customerRepository->findOneBy(['customerEmail' => $input->getCustomerEmail()]);

        if (null !== $isDouble) {
            throw new UnprocessableEntityHttpException('Email already registered. Try to login');
        }

        $message = $this->createCustomerCreatedMessage($input);
        $this->customerCreatedProducer->produce($message);
    }

    private function createCustomerCreatedMessage(CustomerCreateInputDto $customer): CustomerCreatedMessage
    {
        return new CustomerCreatedMessage($customer);
    }
}
