<?php

namespace App\Controller\Contact;

use App\CommandHandler\Contact\Edit\ContactEditInputDto;
use App\Entity\Contact;
//use App\Form\Type\ContactDecryptType;
use App\Form\Type\ContactEditType;
use App\Repository\ContactRepository;
use App\Service\CryptoService;
use App\Service\VerificationEmailService;
use App\Service\VerificationWhatsAppService;
use App\Service\VerificationSocialService;
use Doctrine\DBAL\Exception;
use Random\RandomException;
use SodiumException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;

class ContactEditController extends AbstractController
{
    private MessageBusInterface $commandBus;
    private ContactRepository $repository;
    private CryptoService $cryptoService;
    private VerificationEmailService $verificationEmailService;
    private VerificationWhatsAppService $verificationWhatsAppService;
    private VerificationSocialService $verificationSocialService;
    public function __construct(
        ContactRepository $repository,
        MessageBusInterface $commandBus,
        CryptoService $cryptoService,
        VerificationEmailService $verificationEmailService,
        VerificationWhatsAppService $verificationWhatsAppService,
        VerificationSocialService $verificationSocialService
    )
    {
        $this->repository = $repository;
        $this->commandBus = $commandBus;
        $this->cryptoService = $cryptoService;
        $this->verificationEmailService = $verificationEmailService;
        $this->verificationWhatsAppService = $verificationWhatsAppService;
        $this->verificationSocialService = $verificationSocialService;
    }

    /**
     * @throws SodiumException|RandomException
     * @throws TransportExceptionInterface|\Doctrine\DBAL\Exception
     */
    public function edit(int $contactId, Request $request): Response
    {
        $currentCustomer = $this->getUser();

        $contact = $this->repository->getOneBy(['id' => $contactId]);
        if (!$contact instanceof Contact) {
            throw new \UnexpectedValueException('There is no Contact with id ' . $contactId);
        }

        $contactCustomer = $contact->getCustomer();

        if ($contactCustomer !== $currentCustomer) {
            throw new \UnexpectedValueException('It is not your Contact');
        }

        $dto = new ContactEditInputDto($contactCustomer, $contact->getId());

        $dto
            ->setContactTypeEnum($contact->getContactTypeEnum())
            ->setCountryCode($contact->getCountryCode())
            ->setValue(
                $this->cryptoService->decryptData(
                    $contact->getValue()
                )
            )
            ->setIsVerified($contact->getIsVerified())
        ;


        $form = $this->createForm(
            ContactEditType::class,
            $dto,
            [
                'type' => $contact->getContactTypeEnum(),
                'countryCode' => $contact->getCountryCode(),
                'isVerified' => $contact->getIsVerified(),
            ]
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            if ($form->get('resend_verification')->isClicked()) {

                switch ($contact->getContactTypeEnum()) {
                    case 'email':
                        $this->verificationEmailService->sendVerificationEmail($contact);
                        $this->addFlash('info', 'Verification email resent successfully.');
                        break;

                    case 'phone':
                        $this->verificationWhatsAppService->sendVerificationWhatsApp($contact);
                        $this->addFlash('info', 'Verification phone resent successfully.');
                        break;

                    case 'social':
                        $this->verificationSocialService->sendVerificationSocial($contact);
                        $this->addFlash('info', 'Verification social resent successfully.');
                        break;
                }

                return $this->redirectToRoute('user_home');
            }

            /** @var ContactEditInputDto $contactData */
            $contactData = $form->getData();

            $envelope = $this->commandBus->dispatch($contactData);

            $handledStamp = $envelope->last(HandledStamp::class);

            if (!$handledStamp) {
                throw new UnprocessableEntityHttpException('500 internal error (CommandBus not responding).');
            }

            $handledResult = $handledStamp->getResult();

            $form1 = $this->createForm(ContactEditType::class, $handledResult);

            $this->addFlash('info', 'Contact updated! Would you like to send Verification message now?');

            return $this->render('contactEdit.html.twig', [
                'form' => $form1->createView(),
                'decodedNote' => true,
            ]);
        }

        return $this->render('contactEdit.html.twig', [
            'form' => $form,
            'decodedNote' => false,
        ]);
    }
}