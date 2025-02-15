<?php

declare(strict_types=1);

namespace App\Queue\Doctrine\Customer;

use App\Entity\Contact;
use App\Entity\Customer;
use App\Enum\CustomerPaymentStatusEnum;
use App\Enum\CustomerSocialAppEnum;
use App\Service\CryptoService;
use App\Service\VerificationEmailService;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Random\RandomException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Serializer\SerializerInterface;
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
        private ContactRepository $contactRepository,
        private CryptoService $cryptoService,
        private VerificationEmailService $verificationEmailService
    ) {
//        $this->logger = $logger;
    }

    /**
     * @throws RandomException
     * @throws TransportExceptionInterface
     * @throws Exception
     * @throws \SodiumException
     */
    public function __invoke(CustomerCreatedMessage $message): void
    {

        $input = $message->getCustomerCreateInputDto();

        $customer = new Customer();

        $customer
            ->setCustomerName($input->getCustomerName())
            ->setCustomerEmail($input->getCustomerEmail())
            ->setPassword(
                $this->passwordHasher->hashPassword(
                    $customer,
                    $input->getPassword()
                )
            )

//            ->setCustomerSocialApp($input->getCustomerSocialApp()) // TODO CustomerSocialAppEnum::from($input->getCustomerSocialApp()))
            ->setCustomerSocialApp(CustomerSocialAppEnum::TELEGRAM)
            ->setCustomerPaymentStatus(CustomerPaymentStatusEnum::PAID) //TODO FOR TESTS ONLY
        ;

        $this->entityManager->persist($customer);

        if ($input->getCustomerEmail()) {
            $this->persistContact($customer, 'email',
                $this->cryptoService->encryptData(
                    $input->getCustomerEmail()
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
     * @throws Exception
     */
    private function sendVerificationEmail(Customer $customer): void
    {
       $customerEmails = $this->contactRepository->findBy(['contactTypeEnum' => 'email', 'customer' => $customer]);

       //TODO we got only one email at this point
       foreach ($customerEmails as $contact) {
           $this->verificationEmailService->sendVerificationEmail($contact, 'en');
       }
    }
}
