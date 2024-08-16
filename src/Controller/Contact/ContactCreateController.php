<?php

namespace App\Controller\Contact;

use App\CommandHandler\Contact\Create\ContactCreateInputDto;
use App\CommandHandler\Contact\Edit\ContactEditInputDto;
use App\Entity\Contact;
//use App\Form\Type\ContactDecryptType;
use App\Entity\Customer;
use App\Form\Type\ContactCreateType;
use App\Form\Type\ContactEditType;
use App\Repository\ContactRepository;
use App\Service\CryptoService;
use App\Service\VerificationEmailService;
use Random\RandomException;
use SodiumException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Annotation\Route;

class ContactCreateController extends AbstractController
{
    private MessageBusInterface $commandBus;
    private ContactRepository $repository;
    private CryptoService $cryptoService;
    private VerificationEmailService $verificationEmailService;
    public function __construct(
        ContactRepository $repository,
        MessageBusInterface $commandBus,
        CryptoService $cryptoService,
        VerificationEmailService $verificationEmailService
    )
    {
        $this->repository = $repository;
        $this->commandBus = $commandBus;
        $this->cryptoService = $cryptoService;
        $this->verificationEmailService = $verificationEmailService;
    }

    /**
     * @Route("/contact/{contactId}/edit/", name="note_edit")
     * @throws SodiumException|RandomException
     * @throws TransportExceptionInterface|\Doctrine\DBAL\Exception
     */
    public function create(string $type, Request $request): Response
    {
        $currentCustomer = $this->getUser();

        if (!$currentCustomer instanceof Customer) {
            throw new AccessDeniedException('You must be logged in as a customer to create a contact.');
        }
        $dto = new ContactCreateInputDto($currentCustomer);


        $form = $this->createForm(
            ContactCreateType::class,
            $dto,
            [
                'type' => $type,
                'customer' => $dto->getCustomer(),
            ]
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            /** @var ContactCreateInputDto $contactData */
            $contactData = $form->getData();

            $envelope = $this->commandBus->dispatch($contactData);

            $handledStamp = $envelope->last(HandledStamp::class);

            if (!$handledStamp) {
                throw new UnprocessableEntityHttpException('500 internal error (CommandBus not responding).');
            }

            $this->addFlash('info', 'Contact created! Would you like to send Verification email now?');

            return $this->redirectToRoute('user_home');
        }

        return $this->render('contactEdit.html.twig', [
            'form' => $form,
            'decodedNote' => false,
        ]);
    }
}