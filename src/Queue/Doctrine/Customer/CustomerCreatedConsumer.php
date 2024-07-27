<?php

declare(strict_types=1);

namespace App\Queue\Doctrine\Customer;

use App\Entity\Contact;
use App\Entity\Customer;
use App\Entity\VerificationToken;
use App\Enum\ContactTypeEnum;
use App\Enum\CustomerSocialAppEnum;
use App\Repository\CustomerRepository;
use App\Service\CryptoService;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
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

#[AsMessageHandler]
class CustomerCreatedConsumer
{
    public function __construct(
        protected SerializerInterface $serializer,
        protected MessageBusInterface $commandBus,
        protected UserPasswordHasherInterface $passwordHasher,
        private EntityManagerInterface $entityManager,
        private MailerInterface $mailer,
        private UrlGeneratorInterface $urlGenerator,
        private ContactRepository $contactRepository,
        private CryptoService $cryptoService,
//        LoggerInterface $logger
    ) {}

    /**
     *
     */
    public function __invoke(CustomerCreatedMessage $message,): void
    {
//        try {
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
            ->setCustomerSocialApp($input->getCustomerSocialApp()) //CustomerSocialAppEnum::from($input->getCustomerSocialApp()))
        ;

        $this->entityManager->persist($customer);

        if ($input->getCustomerEmail()){
            $this->persistContact($customer, 'email',
                $this->cryptoService->encryptData(
                    $input->getCustomerEmail()
                )
            );
        }

        if ($input->getCustomerSecondEmail()){
            $this->persistContact($customer, 'email',
                $this->cryptoService->encryptData(
                    $input->getCustomerSecondEmail()
                )
            );
        }

        if ($input->getCustomerFirstPhone()){
            $this->persistContact($customer, 'phone',
                $this->cryptoService->encryptData(
                    $input->getCustomerFirstPhone()
                ), $input->getCustomerCountryCode());
        }

        if ($input->getCustomerSecondPhone()){
            $this->persistContact($customer, 'phone',
                $this->cryptoService->encryptData(
                    $input->getCustomerSecondPhone()
                ), $input->getCustomerCountryCode());
        }

//     TODO: Logs

//        $this->logger->info('Customer created successfully.', ['customerEmail' => $customer->getCustomerEmail()]);
//    } catch (\Exception $e) {
//        $this->logger->error('Error creating customer: ' . $e->getMessage(), ['exception' => $e]);
//            // mark the message as failed, etc.???
//    }

        $token = new VerificationToken(
            $customer,
            'email',
            Uuid::v4()->toRfc4122(),
            new DateTimeImmutable('+1 hour')
        );
        $this->entityManager->persist($token);

        $this->entityManager->flush();

        $this->sendVerificationEmail($customer, $token);
    }

    private function persistContact(Customer $customer, string $type, ?string $value, ?string $countryCode = null): void
    {
        if ($value) {
            $contact = new Contact();
            $contact->setCustomer($customer)
                ->setContactTypeEnum($type)
                ->setValue($value);

            if ($countryCode && $type !== 'email') {
                $contact->setCountryCode($countryCode);
            }

            $this->entityManager->persist($contact);
        }
    }

//    /**
//     * @throws TransportExceptionInterface
//     */
    private function sendVerificationEmail(Customer $customer, VerificationToken $token): void
    {
       $customerEmails = $this->contactRepository->findBy(['contactTypeEnum' => 'email', 'customer' => $customer]);

//       foreach ($customerEmails as $contact) {
//           $verificationUrl = $this->urlGenerator->generate('email_verification_route', [
//               'token' => $token->getToken()
//           ], UrlGeneratorInterface::ABSOLUTE_URL);
//           ;
            // Ensure you decrypt the email before sending
//            $email = $this->cryptoService->decryptData($contact->getValue());

//          TODO EMAILS

//           $email = (new Email())
//               ->from('no-reply@digital-inheritance.com')
//               ->to($email)
//               ->subject('Email Verification')
//               ->html('<p>Thank you for registering! Please verify your email by clicking on the following link: <a href="' . $verificationUrl . '">Verify Email</a></p>');
//
//           $this->mailer->send($email);
//       }
    }
}
