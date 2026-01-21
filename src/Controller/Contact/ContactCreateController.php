<?php

namespace App\Controller\Contact;

use App\CommandHandler\Contact\Create\ContactCreateInputDto;
use App\Entity\Customer;
use App\Form\Type\ContactCreateType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Contracts\Translation\TranslatorInterface;

class ContactCreateController extends AbstractController
{
    public function __construct(
        private MessageBusInterface      $commandBus,
        private TranslatorInterface      $translator
    )
    {
    }

    public function create(string $type, Request $request): Response
    {
        $currentCustomer = $this->getUser();

        if (!$currentCustomer instanceof Customer) {
            $this->addFlash('warning', $this->translator->trans('errors.flash.login_required'));
            return $this->redirectToRoute('user_login');
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

            $this->addFlash('info', $this->translator->trans('errors.flash.contact_created'));

            return $this->redirectToRoute('user_home');
        }

        return $this->render('contactEdit.html.twig', [
            'form' => $form,
            'decodedNote' => false,
        ]);
    }
}