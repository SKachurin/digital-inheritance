<?php

declare(strict_types=1);

namespace App\CommandHandler\Beneficiary\Create;

use App\Entity\Contact;
use App\Entity\Beneficiary;
use App\Service\CryptoService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\QueryException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsMessageHandler]
class BeneficiaryCreateHandler
{
    public function __construct(
        private EntityManagerInterface        $entityManager,
        private CryptoService                 $cryptoService,
        protected UserPasswordHasherInterface $passwordHasher,
    )
    {
    }

    /**
     * @throws QueryException
     */
    public function __invoke(BeneficiaryCreateInputDto $input): Beneficiary
    {
        $beneficiary = new Beneficiary($input->getBeneficiaryName());
        $customer = $input->getCustomer();

        $beneficiary
            ->setCustomer($customer)
            ->setBeneficiaryFullName(
                $this->cryptoService->encryptData(
                    $input->getBeneficiaryFullName()
                )
            );

        if ($input->getCustomerFullName() !== null) {
            $customer->setCustomerFullName(
                $this->cryptoService->encryptData(
                    $input->getCustomerFullName()
                )
            );
            // NB: $customer is already managed, so I can persist the change on flush
        }

        $this->entityManager->persist($beneficiary);

        if ($input->getBeneficiaryEmail()){
            $this->persistContact($beneficiary, 'email',
                $this->cryptoService->encryptData(
                    $input->getBeneficiaryEmail()
                )
            );
        }

        if ($input->getBeneficiarySecondEmail()){
            $this->persistContact($beneficiary, 'email',
                $this->cryptoService->encryptData(
                    $input->getBeneficiarySecondEmail()
                )
            );
        }

        if ($input->getBeneficiaryFirstPhone()){
            $this->persistContact($beneficiary, 'phone',
                $this->cryptoService->encryptData(
                    $input->getBeneficiaryFirstPhone()
                ), $input->getBeneficiaryCountryCode());
        }

        if ($input->getBeneficiarySecondPhone()){
            $this->persistContact($beneficiary, 'phone',
                $this->cryptoService->encryptData(
                    $input->getBeneficiarySecondPhone()
                ), $input->getBeneficiaryCountryCode());
        }

        $this->entityManager->flush();

        return $beneficiary;
    }

    private function persistContact(Beneficiary $beneficiary, string $type, ?string $value, ?string $countryCode = null): void
    {
        if ($value) {
            $contact = new Contact();
            $contact->setBeneficiary($beneficiary)
                ->setContactTypeEnum($type)
                ->setValue($value);

            if ($countryCode && $type === 'phone' ) {
                $contact->setCountryCode($countryCode);
            }

            $this->entityManager->persist($contact);
        }
    }
}
