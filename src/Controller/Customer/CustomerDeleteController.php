<?php

declare(strict_types=1);

namespace App\Controller\Customer;

use App\CommandHandler\Customer\Delete\CustomerDeleteInputDto;
use App\Entity\Customer;
use App\Form\Type\CustomerVerifiedContactsType;
use App\Message\CustomerDeletedMessage;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;

class CustomerDeleteController extends AbstractController
{
    public function __construct(
        private readonly MessageBusInterface    $commandBus,
    )
    {}

    /**
     * @throws Exception
     */
    public function __invoke( Request $request): Response
    {
        $customer = $this->getUser();

        if (!$customer instanceof Customer) {
            return $this->redirectToRoute('user_login');
        }


        $form = $this->createForm(CustomerVerifiedContactsType::class, null, [
            'customer' => $customer
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            /** @var CustomerDeleteInputDto $formData */
            $formData = $form->getData();

    //            $this->commandBus->dispatch($formData);
            $this->commandBus->dispatch(new CustomerDeletedMessage($customer->getId(), $formData->getContactType()));

            return $this->render('user/dashboard/deleteRequest.html.twig', [
                'sent' => true,
            ]);
        }

        return $this->render('user/dashboard/deleteRequest.html.twig', [
            'sent' => false,
            'form' => $form->createView(),
        ]);
    }
}