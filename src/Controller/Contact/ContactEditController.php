<?php

declare(strict_types=1);

namespace App\Controller\Contact;

use App\CommandHandler\Contact\Edit\ContactEditInputDto;
use App\Entity\Contact;
//use App\Form\Type\ContactDecryptType;
use App\Entity\Customer;
use App\Form\Type\ContactEditType;
use App\Repository\ContactRepository;
use App\Service\CryptoService;
use App\Service\VerificationEmailService;
use App\Service\VerificationWhatsAppService;
use App\Service\VerificationSocialService;
use Random\RandomException;
use SodiumException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Contracts\Translation\TranslatorInterface;

class ContactEditController extends AbstractController
{
    public function __construct(
        private ContactRepository            $repository,
        private MessageBusInterface          $commandBus,
        private CryptoService                $cryptoService,
        private VerificationEmailService     $verificationEmailService,
        private VerificationWhatsAppService  $verificationWhatsAppService,
        private VerificationSocialService    $verificationSocialService,
        private readonly TranslatorInterface $translator
    )
    {
    }

    /**
     * @throws SodiumException|RandomException
     * @throws TransportExceptionInterface|\Doctrine\DBAL\Exception
     */
    public function edit(int $contactId, Request $request): Response
    {
        $currentCustomer = $this->getUser();

        if (!$currentCustomer instanceof Customer) {
            return $this->redirectToRoute('user_login');
        }

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
                'allow_extra_fields' => true,
            ]
        );

        $form->handleRequest($request);

        $lang = $currentCustomer->getLang() ?? 'en';

        $message = $this->translator->trans('messages.verification', [], 'messages', $lang);

        if ($form->isSubmitted() && $form->isValid()) {

            if ($form->has('resend_verification') && $form->get('resend_verification')->isClicked()) {

                switch ($contact->getContactTypeEnum()) {
                    case 'email':
                        $this->verificationEmailService->sendVerificationEmail($contact, $message);
                        $this->addFlash('info', $this->translator->trans('errors.flash.email_sent'));
                        break;

                    case 'phone':
                        $this->verificationWhatsAppService->sendVerificationWhatsApp($contact, $message);
                        $this->addFlash('info', $this->translator->trans('errors.flash.phone_sent'));
                        break;

                    case 'social':
                        try {
                            $this->verificationSocialService->sendVerificationSocial($contact, $message);
                            $this->addFlash('info', $this->translator->trans('errors.flash.social_sent'));
                        } catch (\Exception $e) {
                            //TODO monitoring
                            //'Error: ' . $e->getMessage()';
                            $this->addFlash('danger', $this->translator->trans('errors.flash.fail_to_sent') );
                        }
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

            $this->addFlash('info', $this->translator->trans('errors.flash.contact_updated'));

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