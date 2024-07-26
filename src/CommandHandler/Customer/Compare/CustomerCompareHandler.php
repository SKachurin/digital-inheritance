<?php

declare(strict_types=1);

namespace App\CommandHandler\Customer\Compare;

use App\CommandHandler\Customer\Create\CustomerCreateInputDto;
use App\Message\CustomerWithContactsMessage;
use App\Queue\Doctrine\Customer\CustomerCreatedProducer;
use App\Repository\CustomerRepository;
use App\Entity\Customer;
use App\Queue\Doctrine\Customer\CustomerCreatedMessage;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\QueryException;
use phpDocumentor\Reflection\Types\Void_;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[AsMessageHandler]
class CustomerCompareHandler
{
    public function __construct(
//        private EntityManagerInterface $entityManager,
//        private CustomerRepository $customerRepository,
//        private CustomerCreatedProducer $customerCreatedProducer,
    ) {}

    /**
     */
    public function __invoke(CustomerWithContactsMessage $message): array
    {

        $customer = $message->getCustomer();
        $contacts = $message->getContacts();

        $dto1 = $this->createCustomerCompareOutputDto1($customer, $contacts);
        $dto2 = $this->createCustomerCompareOutputDto1($customer, $contacts);

        return [$dto1, $dto2];
    }

    private function createCustomerCompareOutputDto1(Customer $customer, array $contacts): CustomerCompareOutputDto1
    {
        $dto = new CustomerCompareOutputDto1($customer);

        foreach ($contacts as $contact) {
            if ($contact->getContactTypeEnum() === 'email') {
                if ($dto->getCustomerEmail() === '') {
                    $dto->setCustomerSecondEmail($contact->getValue());
                }
            } elseif ($contact->getContactTypeEnum() === 'phone') {
                if ($dto->getCustomerFirstPhone() === null) {
                    $dto->setCustomerFirstPhone($contact->getValue());
                    $dto->setCustomerCountryCode($contact->getCountryCode());
                } elseif ($dto->getCustomerSecondPhone() === null) {
                    $dto->setCustomerSecondPhone($contact->getValue());
                }
            }
        }

        return $dto;
    }

//    private function createCustomerCompareOutputDto2(Customer $customer, array $contacts): CustomerCompareOutputDto2
//    {
//        return new CustomerCompareOutputDto2($customer, $contacts);
//    }
}
