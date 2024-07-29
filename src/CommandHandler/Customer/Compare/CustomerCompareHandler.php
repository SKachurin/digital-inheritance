<?php

declare(strict_types=1);

namespace App\CommandHandler\Customer\Compare;

use App\Message\CustomerWithContactsMessage;
use App\Entity\Customer;
use App\Service\CryptoService;
use SodiumException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class CustomerCompareHandler
{
    private CryptoService $cryptoService;
    public function __construct(
        CryptoService $cryptoService,
    ) {
        $this->cryptoService = $cryptoService;
    }

    /**
     */
    public function __invoke(CustomerWithContactsMessage $message): array
    {

        $customer = $message->getCustomer();
        $contacts = $message->getContacts();

        $dto1 = $this->createCustomerCompareOutputDto1($customer, $contacts);
        $dto2 = $this->createCustomerCompareOutputDto2($dto1);

        return [$dto1, $dto2];
    }

    /**
     * @throws SodiumException
     */
    private function createCustomerCompareOutputDto1(Customer $customer, array $contacts): CustomerCompareOutputDto1
    {
        $dto = new CustomerCompareOutputDto1($customer);

        foreach ($contacts as $contact) {

            if ($contact->getContactTypeEnum() === 'email') {
                if ($dto->getCustomerEmail() === null) {
                    $dto->setCustomerEmail($customer->getCustomerEmail());
                } else {
                    $dto->setCustomerSecondEmail($contact->getValue());
                }
            }
            if ($contact->getContactTypeEnum() === 'phone') {
                $dto->setCustomerCountryCode($contact->getCountryCode());
                if ($dto->getCustomerFirstPhone() === null) {
                    $dto->setCustomerFirstPhone($contact->getValue());
                } else {
                    $dto->setCustomerSecondPhone($contact->getValue());
                }
            }
        }

        return $dto;
    }

    /**
     * @throws SodiumException
     */
    private function createCustomerCompareOutputDto2(CustomerCompareOutputDto1 $dto): CustomerCompareOutputDto2
    {
        $dto2 = new CustomerCompareOutputDto2($dto);
        $dto2->setCustomerFullName($this->cryptoService->decryptData($dto->getCustomerFullName()));
        $dto2->setCustomerFirstQuestion($this->cryptoService->decryptData($dto->getCustomerFirstQuestion()));
        $dto2->setCustomerFirstQuestionAnswer(
            $this->cryptoService->decryptData(
                $dto->getCustomerFirstQuestionAnswer()
            )
        );
        $dto2->setCustomerSecondQuestion($this->cryptoService->decryptData($dto->getCustomerSecondQuestion()));
        $dto2->setCustomerSecondQuestionAnswer(
            $this->cryptoService->decryptData(
                $dto->getCustomerSecondQuestionAnswer()
            )
        );
        $dto2->setCustomerEmail($dto->getCustomerEmail());
        $dto2->setCustomerName($dto->getCustomerName());
        $dto2->setCustomerSecondEmail($this->cryptoService->decryptData($dto->getCustomerSecondEmail()));
        $dto2->setCustomerCountryCode($dto->getCustomerCountryCode());
        $dto2->setCustomerFirstPhone($this->cryptoService->decryptData($dto->getCustomerFirstPhone()));
        $dto2->setCustomerSecondPhone($this->cryptoService->decryptData($dto->getCustomerSecondPhone()));
        $dto2->setCustomerSocialApp($dto->getCustomerSocialApp());
        $dto2->setCustomerOkayPassword($dto->getCustomerOkayPassword());
        $dto2->setPassword($dto->getPassword());

        return $dto2;

    }
}
