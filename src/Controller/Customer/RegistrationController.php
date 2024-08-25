<?php
namespace App\Controller\Customer;

use App\CommandHandler\Customer\Create\CustomerCreateInputDto;
use App\Enum\CustomerSocialAppEnum;
use App\Form\Type\RegistrationType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

class RegistrationController extends AbstractController
{
    private MessageBusInterface $commandBus;



    public function __construct(MessageBusInterface $commandBus)
    {
        $this->commandBus = $commandBus;
    }

    public function new(Request $request): Response
    {
        $customer = $this->getUser();

        if ($customer instanceof \App\Entity\Customer) {
            return $this->redirectToRoute('user_home');
        }

        $customer = new CustomerCreateInputDto(
            'enter something',
            'viva-natura1@yandex.ru',
            'it should be not obvious for strangers',
            'Love',
            'passwordOkay',
            'password',
            CustomerSocialAppEnum::NONE,
            'https://fb.com/something/',
            'viva-n@yandex.ru',
            'Winnie the Pooh',
            '995',
            '555555555',
            '',
            'All we need is:',
            'Love'

        );

        $form = $this->createForm(RegistrationType::class, $customer);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            /** @var CustomerCreateInputDto $customerData */
            $customerData = $form->getData();

            $envelope = $this->commandBus->dispatch($customerData);

            $handledStamp = $envelope->last(HandledStamp::class);

            if (!$handledStamp) {
                throw new UnprocessableEntityHttpException('500 internal error (CommandBus not responding).');
            }

            $this->addFlash('success', 'Your registration is being processed. You will receive a confirmation email once it is complete.');

            return $this->redirectToRoute('user_login');
        }

       return $this->render('user/registration.html.twig', [
            'form' => $form,
       ]);

    }

}