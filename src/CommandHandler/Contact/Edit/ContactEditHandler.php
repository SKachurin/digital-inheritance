<?php

declare(strict_types=1);

namespace App\CommandHandler\Contact\Edit;

use App\Entity\Contact;
use App\Repository\ActionRepository;
use App\Service\CryptoService;
use App\Service\Phone\CountryCallingCodeProvider;
use App\Service\SocialAppLinkNormalizer;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Random\RandomException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class ContactEditHandler
{
    public function __construct(
        private readonly CryptoService $cryptoService,
        private readonly EntityManagerInterface $entityManager,
        private readonly SocialAppLinkNormalizer $socialAppLinkNormalizer,
        private readonly ActionRepository $actionRepository,
        private readonly CountryCallingCodeProvider $callingCodes,
    ) {}

    /**
     * @throws OptimisticLockException
     * @throws RandomException
     * @throws ORMException
     * @throws \SodiumException
     */
    public function __invoke(ContactEditInputDto $input): ContactEditInputDto
    {
        $contact = $this->entityManager->find(Contact::class, $input->getId());

        if (!$contact instanceof Contact) {
            throw new \RuntimeException('Contact not found.');
        }

        // ---- PHONE: country code normalization ----
        if ($input->getContactTypeEnum() === 'phone') {
            $normalizedCode = $this->callingCodes->normalize($input->getCountryCode());

            if ($normalizedCode !== null && $normalizedCode !== $contact->getCountryCode()) {
                $contact->setCountryCode($normalizedCode);
                $contact->setIsVerified(false);
            }
        }

        // ---- VALUE normalization ----
        $newValue = $input->getValue();

        if ($input->getContactTypeEnum() === 'social') {
            $newValue = $this->socialAppLinkNormalizer->normalize($newValue);
        }

        $currentValue = $this->cryptoService->decryptData($contact->getValue());

        if ($newValue !== null && $newValue !== $currentValue) {
            $contact->setValue(
                $this->cryptoService->encryptData($newValue)
            );
            $contact->setIsVerified(false);

            if ($action = $this->actionRepository->findOneBy(['contact' => $contact])) {
                $this->entityManager->remove($action);
            }
        }

        $this->entityManager->flush();

        return $input;
    }
}