<?php
namespace App\Controller\Beneficiary;

use App\CommandHandler\Beneficiary\Create\BeneficiaryCreateInputDto;
use App\CommandHandler\Customer\Create\CustomerCreateInputDto;
use App\Enum\CustomerSocialAppEnum;
use App\Form\Type\BeneficiaryCreateType;
use App\Form\Type\RegistrationType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

class BeneficiaryCreateController extends AbstractController
{
    private MessageBusInterface $commandBus;



    public function __construct(MessageBusInterface $commandBus)
    {
        $this->commandBus = $commandBus;
    }

    public function create(Request $request): Response
    {
        $customer = $this->getUser();

        if (! $customer instanceof \App\Entity\Customer) {
            return $this->redirectToRoute('user_login');
        }

        $customer = new BeneficiaryCreateInputDto();

        $form = $this->createForm(BeneficiaryCreateType::class, $customer);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            /** @var BeneficiaryCreateInputDto $customerData */
            $customerData = $form->getData();

            $envelope = $this->commandBus->dispatch($customerData);

            $handledStamp = $envelope->last(HandledStamp::class);

            if (!$handledStamp) {
                throw new UnprocessableEntityHttpException('500 internal error (CommandBus not responding).');
            }


            $this->addFlash('success', 'Your Heir is being processed.');

            $heir = $handledStamp->getResult();
            $heirId = $heir->getId();

            $form1 = $this->createForm(NoteCreationType1::class, $heir, ['customerId' => $customer->getId()]);

            return $this->render('noteCreate.html.twig', [
                'form' => $form1,
                'decodedNote' => true,
                'noteId' => $heirId
            ]);
        }

       return $this->render('user/registration.html.twig', [
            'form' => $form,
       ]);

    }

}