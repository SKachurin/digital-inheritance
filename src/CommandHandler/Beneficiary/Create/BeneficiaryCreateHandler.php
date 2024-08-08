<?php

declare(strict_types=1);

namespace App\CommandHandler\Beneficiary\Create;

use App\CommandHandler\Customer\Create\CustomerCreateInputDto;
use App\Entity\Contact;
use App\Queue\Doctrine\Customer\CustomerCreatedProducer;
use App\Repository\BeneficiaryRepository;
use App\Repository\CustomerRepository;
use App\Entity\Beneficiary;
use App\Queue\Doctrine\Customer\CustomerCreatedMessage;
use App\Service\CryptoService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\QueryException;
use phpDocumentor\Reflection\Types\Void_;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[AsMessageHandler]
class BeneficiaryCreateHandler
{
    private CryptoService $cryptoService;
    private EntityManagerInterface $entityManager;
    protected UserPasswordHasherInterface $passwordHasher;

    public function __construct(
        EntityManagerInterface $entityManager,
         CryptoService $cryptoService,
         UserPasswordHasherInterface $passwordHasher,
    ) {
        $this->entityManager = $entityManager;
        $this->cryptoService = $cryptoService;
    }

    /**
     * @throws QueryException
     */
    public function __invoke(BeneficiaryCreateInputDto $input): Beneficiary
    {
        $beneficiary = new Beneficiary($input->getBeneficiaryName());

        $beneficiary
            ->setBeneficiaryFullName(
                $this->cryptoService->encryptData(
                    $input->getBeneficiaryFullName()
                )
            )
            ->setBeneficiaryFirstQuestion(
                $this->cryptoService->encryptData(
                    $input->getBeneficiaryFirstQuestion()
                )
            )
            ->setBeneficiaryFirstQuestionAnswer(
                $this->passwordHasher->hashPassword(
                    $beneficiary,
                    $input->getBeneficiaryFirstQuestionAnswer()
                )
            )
            ->setBeneficiarySecondQuestion(
                $this->cryptoService->encryptData(
                    $input->getBeneficiarySecondQuestion()
                )
            )
            ->setBeneficiarySecondQuestionAnswer(
                $this->passwordHasher->hashPassword(
                    $beneficiary,
                    $input->getBeneficiarySecondQuestionAnswer()
                )
            )
        ;

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
