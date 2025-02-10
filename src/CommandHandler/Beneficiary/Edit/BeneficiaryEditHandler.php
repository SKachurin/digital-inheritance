<?php

declare(strict_types=1);

namespace App\CommandHandler\Beneficiary\Edit;

use App\Entity\Contact;
use App\Repository\BeneficiaryRepository;
use App\Entity\Beneficiary;
use App\Service\CryptoService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\QueryException;
use Random\RandomException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class BeneficiaryEditHandler
{
    public function __construct(
        private EntityManagerInterface      $entityManager,
        private CryptoService               $cryptoService,
        private BeneficiaryRepository       $beneficiaryRepository,
    )
    {
    }

    /**
     * @throws QueryException
     */
    public function __invoke(BeneficiaryEditInputDto $input): Beneficiary
    {
        $beneficiary = $this->beneficiaryRepository->find($input->getId());

        $beneficiary
            ->setBeneficiaryName($input->getBeneficiaryName())
            ->setBeneficiaryFullName(
                $this->cryptoService->encryptData(
                    $input->getBeneficiaryFullName()
                )
            )
        ;

        $this->entityManager->persist($beneficiary);

        $this->updateContacts($beneficiary, $input);

        $this->entityManager->flush();

        return $beneficiary;
    }

    /**
     * @throws RandomException
     * @throws \SodiumException
     */
    private function updateContacts(Beneficiary $beneficiary, BeneficiaryEditInputDto $input): void
    {
        // Remove existing contacts
        foreach ($beneficiary->getContacts() as $contact) {
            $this->entityManager->remove($contact);
        }

        // Add updated contacts
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
    }

    private function persistContact(Beneficiary $beneficiary, string $type, ?string $value, ?string $countryCode = null): void
    {
        if ($value) {
            $contact = new Contact();
            $contact->setBeneficiary($beneficiary)
                ->setContactTypeEnum($type)
                ->setValue($value);

            if ($countryCode && $type === 'phone') {
                $contact->setCountryCode($countryCode);
            }

            $this->entityManager->persist($contact);
        }
    }
}
