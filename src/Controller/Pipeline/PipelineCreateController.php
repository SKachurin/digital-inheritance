<?php

namespace App\Controller\Pipeline;

use App\CommandHandler\Pipeline\Create\PipelineCreateInputDto;
use App\CommandHandler\Pipeline\Create\ActionDto;
use App\Entity\Pipeline;
use App\Entity\Customer;
use App\Enum\ActionStatusEnum;
use App\Form\Type\PipelineCreateType;
use App\Repository\ActionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class PipelineCreateController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface        $entityManager,
        private ActionRepository              $actionRepository,
        protected UserPasswordHasherInterface $passwordHasher,
        private TranslatorInterface           $translator
    )
    {
    }

    public function create(Request $request): Response
    {
        $customer = $this->getUser();

        if (!$customer instanceof Customer) {
            return $this->redirectToRoute('user_login');
        }

        // Fetch customer's actions
        $customerActions = $this->actionRepository->customerHasActions($customer);

        if (empty($customerActions)) {
            $this->addFlash('info', $this->translator->trans('errors.flash.verify_contact'));
            return $this->redirectToRoute('user_home');
        }

        $pipelineDto = new PipelineCreateInputDto($customer->getCustomerOkayPassword() ?? '', $customer);

        // Initialize with two empty actions
        $actionDtos = [];
        for ($i = 0; $i < count($customerActions); $i++) {
            $actionDto = new ActionDto();
            $actionDto->setPosition($i + 1);
            $actionDtos[] = $actionDto;
        }
        $pipelineDto->setActions($actionDtos);

        // Create form to capture input data
        $form = $this->createForm(PipelineCreateType::class, $pipelineDto, [
            'customerActions' => $customerActions,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var PipelineCreateInputDto $pipelineDto */
            $pipelineDto = $form->getData();

            //Set Customer Okay Password Hash
            $customer->setCustomerOkayPassword(
                $this->passwordHasher->hashPassword(
                    $customer,
                    $pipelineDto->getCustomerOkayPassword()
                )
            );


            // Create a new Pipeline entity
            $pipeline = new Pipeline(
                $customer,
                ActionStatusEnum::ACTIVATED,
                []
            );

            // Sort the actions array based on the position field
            $actions = $pipelineDto->getActions();
            usort($actions, function (ActionDto $a, ActionDto $b) {
                return $a->getPosition() <=> $b->getPosition();
            });
            $pipelineDto->setActions($actions);

            $actionSequence = [];
            foreach ($pipelineDto->getActions() as $actionDto) {
                if (!$actionDto->getActionType() || !$actionDto->getInterval()) {
                    continue;
                }

                // find Action and update it Interval and fill Actions properties in Pipeline
                $action = $this->actionRepository->findOneBy(['customer' => $customer, 'actionType' => $actionDto->getActionType()->value]);

                //on creation set Pipeline fields from First Action(DTO) in sequence
                if($actionDto->getPosition() == '1'){
                    $pipeline->setActionType($action->getActionType());
                    $pipeline->setActionStatus(ActionStatusEnum::fromString($action->getStatus()));
                }
                $action->setTimeInterval($actionDto->getInterval());
                $this->entityManager->persist($action);

                $actionSequence[] = [
                    'position' => $actionDto->getPosition(),
                    'actionType' => $actionDto->getActionType()->value,
                    'interval' => $actionDto->getInterval()->value,
                ];
            }
            if ($pipeline->getActionStatus() == null) {
                $pipeline->setActionStatus(ActionStatusEnum::ACTIVATED);
            }
            $pipeline->setPipelineStatus(ActionStatusEnum::ACTIVATED);
            $pipeline->setActionSequence($actionSequence);

            $this->entityManager->persist($pipeline);
            $this->entityManager->flush();

            $this->addFlash('success', $this->translator->trans('errors.flash.pipeline_created'));
            return $this->redirectToRoute('user_home');
        }

        return $this->render('pipeline/pipelineCreate.html.twig', [
            'form' => $form->createView(),
            'actions' => $customerActions,
            'pipeline' => null, // No pipeline yet
        ]);
    }
}
