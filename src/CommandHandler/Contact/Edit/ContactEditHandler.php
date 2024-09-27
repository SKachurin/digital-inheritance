<?php

declare(strict_types=1);

namespace App\CommandHandler\Contact\Edit;

use App\Entity\Contact;
use App\Service\CryptoService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Exception;
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
    public function __invoke(ContactEditInputDto $input): ContactEditInputDto
    {
        $contact = $this->entityManager->find(Contact::class, $input->getId());


        if (!$contact) {
            throw new \Exception('Contact not found.');
        }

        $newValue = $input->getValue();

        if ($input->getContactTypeEnum() == 'social'){
            $newValue = $this->normalizeSocialAppLink($input->getValue());
        }

        if ($newValue !== null && $newValue !== $this->cryptoService->decryptData($contact->getValue())) {
            $encryptedValue = $this->cryptoService->encryptData($newValue);
            $contact->setValue($encryptedValue);

//            TODO change email in Customer or Heir
//            $customer = $input->getCustomer();
//            if ($customer->getCustomerEmail())

            $input->setValue($newValue);

            $this->entityManager->persist($contact);
            $this->entityManager->flush();
        }


        return $input;
    }

    private function normalizeSocialAppLink(string $link): string
    {
        $link = trim($link);

        if (str_starts_with($link, '@')) {
            return $link;
        }

        if (preg_match('/^\+/', $link)) {
            return preg_replace('/[^+\d]/', '', $link);
        }

        if (str_starts_with($link, 'https://t.me/')) {
            $parsedLink = parse_url($link, PHP_URL_PATH);
            $username = trim($parsedLink, '/');
            return '@' . $username;
        }

        if (str_starts_with($link, 't.me/')) {
            $username = substr($link, strlen('t.me/'));
            $username = trim($username, '/');
            return '@' . $username;
        }

        // Assume any remaining input is a username and prepend '@'
        return '@' . ltrim($link, '@');
    }
}
