<?php

declare(strict_types=1);

namespace App\CommandHandler\Contact\Create;

use App\CommandHandler\Contact\Edit\ContactEditInputDto;
use App\Entity\Contact;
use App\Service\CryptoService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Exception;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ContactCreateHandler
{
    private CryptoService $cryptoService;
    private EntityManagerInterface $entityManager;
//    private LoggerInterface $logger;

    public function __construct(
        CryptoService $cryptoService,
        EntityManagerInterface $entityManager,
//        LoggerInterface $logger
    ) {
        $this->cryptoService = $cryptoService;
        $this->entityManager = $entityManager;
//        $this->logger = $logger;
    }


    /**
     * @throws Exception
     * @throws ORMException
     */
    public function __invoke(ContactCreateInputDto $input): Contact
    {
        $customer = $input->getCustomer();
        $contact = new Contact();
        $contact
            ->setCustomer($customer)
            ->setContactTypeEnum($input->getContactTypeEnum())
            ->setValue(
                $this->cryptoService->encryptData(
                    $input->getValue()
                )
            )
        ;

        if ($input->getCountryCode() && $input->getContactTypeEnum() === 'phone' ) {
            $contact->setCountryCode($input->getCountryCode());
        }

        $this->entityManager->persist($contact);



        return $contact;
    }
}
