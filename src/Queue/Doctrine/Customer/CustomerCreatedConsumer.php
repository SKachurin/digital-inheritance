<?php

declare(strict_types=1);

namespace App\Queue\Doctrine\Customer;

use App\Entity\Contact;
use App\Entity\Customer;
use App\Entity\VerificationToken;
use App\Service\CryptoService;
use App\Service\VerificationEmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Mime\Email;
use DateTimeImmutable;
use Symfony\Component\Uid\Uuid;
use App\Repository\ContactRepository;
//use Psr\Log\LoggerInterface;

#[AsMessageHandler]
class CustomerCreatedConsumer
{
//    private LoggerInterface $logger;
    public function __construct(
        protected SerializerInterface $serializer,
        protected MessageBusInterface $commandBus,
        protected UserPasswordHasherInterface $passwordHasher,
        private EntityManagerInterface $entityManager,
        private MailerInterface $mailer,
        private UrlGeneratorInterface $urlGenerator,
        private ContactRepository $contactRepository,
        private CryptoService $cryptoService,
        private VerificationEmailService $verificationEmailService
//        LoggerInterface $logger,

    ) {
//        $this->logger = $logger;
    }

    public function __invoke(CustomerCreatedMessage $message,): void
    {

        $input = $message->getCustomerCreateInputDto();

        $customer = new Customer();

        $customer
            ->setCustomerName($input->getCustomerName())
            ->setCustomerEmail($input->getCustomerEmail())
            ->setCustomerOkayPassword(
                $this->passwordHasher->hashPassword(
                    $customer,
                    $input->getCustomerOkayPassword()
                )
            )
            ->setPassword(
                $this->passwordHasher->hashPassword(
                    $customer,
                    $input->getPassword()
                )
            )
            ->setCustomerFullName(
                $this->cryptoService->encryptData(
                    $input->getCustomerFullName()
                )
            )
            ->setCustomerFirstQuestion(
                $this->cryptoService->encryptData(
                    $input->getCustomerFirstQuestion()
                )
            )
            ->setCustomerFirstQuestionAnswer(
                $this->cryptoService->encryptData(
                    $input->getCustomerFirstQuestionAnswer()
                )
            )
            ->setCustomerSecondQuestion(
                $this->cryptoService->encryptData(
                    $input->getCustomerSecondQuestion()
                )
            )
            ->setCustomerSecondQuestionAnswer(
                $this->cryptoService->encryptData(
                    $input->getCustomerSecondQuestionAnswer()
                )
            )
            ->setCustomerSocialApp($input->getCustomerSocialApp()) // TODO CustomerSocialAppEnum::from($input->getCustomerSocialApp()))
        ;

        $this->entityManager->persist($customer);

        if ($input->getCustomerEmail()) {
            $this->persistContact($customer, 'email',
                $this->cryptoService->encryptData(
                    $input->getCustomerEmail()
                )
            );
        }

        if ($input->getCustomerSecondEmail()) {
            $this->persistContact($customer, 'email',
                $this->cryptoService->encryptData(
                    $input->getCustomerSecondEmail()
                )
            );
        }

        if ($input->getCustomerFirstPhone()) {
            $this->persistContact($customer, 'phone',
                $this->cryptoService->encryptData(
                    $input->getCustomerFirstPhone()
                ), $input->getCustomerCountryCode());
        }

        if ($input->getCustomerSecondPhone()) {
            $this->persistContact($customer, 'phone',
                $this->cryptoService->encryptData(
                    $input->getCustomerSecondPhone()
                ), $input->getCustomerCountryCode());
        }

        if ($input->getCustomerSocialAppLink()) {
            $this->persistContact($customer, 'social',
                $this->cryptoService->encryptData(
                    $input->getCustomerSocialAppLink()
                )
            );
        }


        $this->entityManager->flush();

        $this->sendVerificationEmail($customer);
    }

    private function persistContact(Customer $customer, string $type, ?string $value, ?string $countryCode = null): void
    {
        if ($value) {
            $contact = new Contact();
            $contact->setCustomer($customer)
                ->setContactTypeEnum($type)
                ->setValue($value);

            if ($countryCode && $type === 'phone' ) {
                $contact->setCountryCode($countryCode);
            }

            $this->entityManager->persist($contact);
        }
    }


    /**
     * @throws TransportExceptionInterface
     * @throws \SodiumException
     */
    private function sendVerificationEmail(Customer $customer): void
    {
       $customerEmails = $this->contactRepository->findBy(['contactTypeEnum' => 'email', 'customer' => $customer]);

       foreach ($customerEmails as $contact) {
           $this->verificationEmailService->sendVerificationEmail($contact);
       }
    }
}
