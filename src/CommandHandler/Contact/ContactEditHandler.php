<?php

declare(strict_types=1);

namespace App\CommandHandler\Contact;

use App\CommandHandler\Note\Edit\NoteEditInputDto;
use App\CommandHandler\Note\Edit\NoteEditOutputDto;
use App\Entity\Contact;
use App\Service\CryptoService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ContactEditHandler
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
    public function __invoke(ContactEditInputDto $input): Contact
    {
        $contact = $this->entityManager->find(Contact::class, $input->getId());

        if (!$contact) {
            throw new \Exception('Contact not found.');
        }

        $newValue = $input->getValue();

        if ($newValue !== null) {
            $encryptedValue = $this->cryptoService->encryptData($newValue);
            $contact->setValue($encryptedValue);

//            $this->logger->info('Updating contact value.', ['contact_id' => $contact->getId(), 'new_value' => $newValue]);
        }

        $this->entityManager->persist($contact);
        $this->entityManager->flush();

        return $contact;
    }
}
