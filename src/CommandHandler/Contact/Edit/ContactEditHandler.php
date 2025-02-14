<?php

declare(strict_types=1);

namespace App\CommandHandler\Contact\Edit;

use App\Entity\Contact;
use App\Repository\ActionRepository;
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
    private ActionRepository $actionRepository;

//    private LoggerInterface $logger;

    public function __construct(
        CryptoService $cryptoService,
        EntityManagerInterface $entityManager,
        SocialAppLinkNormalizer $socialAppLinkNormalizer,
        ActionRepository $actionRepository
//        LoggerInterface $logger
    ) {
        $this->cryptoService = $cryptoService;
        $this->entityManager = $entityManager;
        $this->socialAppLinkNormalizer = $socialAppLinkNormalizer;
        $this->actionRepository = $actionRepository;
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
            $contact->setIsVerified(false);

//            so Heir do not have email field
//            and for Customer I gonna change this field on this Contact Verification


            $action = $this->actionRepository->findOneBy(['contact' => $contact]);
            if (isset($action)) {
                $this->entityManager->remove($action);
            }

            $this->entityManager->persist($contact);
            $this->entityManager->flush();
        }


        return $input;
    }
}
