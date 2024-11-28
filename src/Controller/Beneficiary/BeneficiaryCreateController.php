<?php
namespace App\Controller\Beneficiary;

use App\CommandHandler\Beneficiary\Create\BeneficiaryCreateInputDto;
use App\Form\Type\BeneficiaryCreateType;
use App\Repository\NoteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

class BeneficiaryCreateController extends AbstractController
{
    private MessageBusInterface $commandBus;
    private NoteRepository $noteRepository;
    private LoggerInterface $logger;
    private ParameterBagInterface $params;
    private EntityManagerInterface $entityManager;


    public function __construct(
        MessageBusInterface $commandBus,
        NoteRepository $noteRepository,
        LoggerInterface $logger,
        ParameterBagInterface $params,
        EntityManagerInterface $entityManager
    )
    {
        $this->commandBus = $commandBus;
        $this->noteRepository = $noteRepository;
        $this->logger = $logger;
        $this->params = $params;
        $this->entityManager = $entityManager;
    }

    /**
     * @throws \Exception
     */
    public function create(Request $request): Response
    {
        $customer = $this->getUser();

        if (! $customer instanceof \App\Entity\Customer) {
            return $this->redirectToRoute('user_login');
        }

        $beneficiary = new BeneficiaryCreateInputDto($customer);

        $form = $this->createForm(BeneficiaryCreateType::class, $beneficiary);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            /** @var BeneficiaryCreateInputDto $beneficiaryData */
            $beneficiaryData = $form->getData();

            $envelope = $this->commandBus->dispatch($beneficiaryData);

            $handledStamp = $envelope->last(HandledStamp::class);

            if (!$handledStamp) {
                throw new UnprocessableEntityHttpException('500 internal error (CommandBus not responding).');
            }

            $this->addFlash('success', 'Your Heir is created.');
            return $this->redirectToRoute('user_home');
        }

       return $this->render('beneficiaryCreate.html.twig', [
            'form' => $form,
       ]);

    }
}