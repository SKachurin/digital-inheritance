<?php

declare(strict_types=1);

namespace App\CommandHandler\Contact\Edit;

use App\Entity\Contact;
use App\Service\CryptoService;
use App\Service\SocialAppLinkNormalizer;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Exception;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ContactEditHandler
{
    private CryptoService $cryptoService;
    private EntityManagerInterface $entityManager;
    private SocialAppLinkNormalizer $socialAppLinkNormalizer;

//    private LoggerInterface $logger;

    public function __construct(
        CryptoService $cryptoService,
        EntityManagerInterface $entityManager,
        SocialAppLinkNormalizer $socialAppLinkNormalizer
//        LoggerInterface $logger
    ) {
        $this->cryptoService = $cryptoService;
        $this->entityManager = $entityManager;
        $this->socialAppLinkNormalizer = $socialAppLinkNormalizer;
//        $this->logger = $logger;
    }


    /**
     * @throws Exception
     * @throws ORMException
     */
    public function __invoke(ContactEditInputDto $input): ContactEditInputDto
    {
        $contact = $this->entityManager->find(Contact::class, $input->getId());


        if (!$contact) {
            throw new \Exception('Contact not found.');
        }

        $newValue = $input->getValue();

        if ($input->getContactTypeEnum() == 'social'){
            $newValue = $this->socialAppLinkNormalizer->normalize($input->getValue());
        }

        if ($newValue !== null && $newValue !== $this->cryptoService->decryptData($contact->getValue())) {
            $encryptedValue = $this->cryptoService->encryptData($newValue);
            $contact->setValue($encryptedValue);

//            TODO change email in Customer or Heir
//            $customer = $input->getCustomer();
//            if ($customer->getCustomerEmail())


//            TODO Delete OLD Action

            $input->setValue($newValue);
            $input->setIsVerified(false);

            $this->entityManager->persist($contact);
            $this->entityManager->flush();
        }


        return $input;
    }
}
