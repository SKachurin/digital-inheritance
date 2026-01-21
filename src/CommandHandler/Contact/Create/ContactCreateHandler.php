<?php

declare(strict_types=1);

namespace App\CommandHandler\Contact\Create;

use App\Entity\Contact;
use App\Service\CryptoService;
use App\Service\Phone\CountryCallingCodeProvider;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ContactCreateHandler
{
    public function __construct(
        private readonly CryptoService $cryptoService,
        private readonly EntityManagerInterface $entityManager,
        private readonly CountryCallingCodeProvider $callingCodes,
    ) {}

    public function __invoke(ContactCreateInputDto $input): Contact
    {
        $contact = new Contact();

        if ($input->getContactTypeEnum() === 'phone') {
            $cc = $this->callingCodes->normalize($input->getCountryCode());
            $contact->setCountryCode($cc);
        }

        $contact
            ->setCustomer($input->getCustomer())
            ->setContactTypeEnum($input->getContactTypeEnum())
            ->setValue(
                $this->cryptoService->encryptData($input->getValue())
            );

        $this->entityManager->persist($contact);

        return $contact;
    }
}
