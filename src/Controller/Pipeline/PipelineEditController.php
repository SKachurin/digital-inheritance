<?php

namespace App\Controller\Pipeline;

use App\CommandHandler\Pipeline\Create\ActionDto;
use App\CommandHandler\Pipeline\Create\PipelineCreateInputDto;
use App\Entity\Customer;
use App\Enum\ActionStatusEnum;
use App\Enum\ActionTypeEnum;
use App\Enum\IntervalEnum;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Form\Type\PipelineCreateType;
use App\Repository\ActionRepository;
use App\Repository\PipelineRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;


class PipelineEditController extends AbstractController
{   //TODO Move everything to Handler with messageBus
    public function __construct(
        private PipelineRepository            $pipelineRepository,
        private ActionRepository              $actionRepository,
        private EntityManagerInterface        $entityManager,
        protected UserPasswordHasherInterface $passwordHasher,
        private TranslatorInterface           $translator
    )
    {
    }

    public function edit(Request $request, int $pipelineId): Response
    {
        $pipeline = $this->pipelineRepository->find($pipelineId);

        if (!$pipeline) {
            throw $this->createNotFoundException('Pipeline not found.');
        }

        $customer = $this->getUser();

        if (!$customer instanceof Customer || $pipeline->getCustomer() !== $customer) {
            throw $this->createAccessDeniedException('You do not have access to this pipeline.');
        }

        $pipelineDto = new PipelineCreateInputDto($customer->getCustomerOkayPassword() ?? '', $customer);

        $actions = [];
        foreach ($pipeline->getActionSequence() as $actionData) {
            $actionDto = new ActionDto();
            $actionDto->setPosition($actionData['position']);

            $actionType = isset($actionData['actionType']) ? ActionTypeEnum::tryFrom($actionData['actionType']) : null;
            $actionDto->setActionType($actionType);

            $interval = isset($actionData['interval']) ? IntervalEnum::tryFrom($actionData['interval']) : null;
            $actionDto->setInterval($interval);

            $actions[] = $actionDto;
        }

        // Sort actions by position
        usort($actions, function (ActionDto $a, ActionDto $b) {
            return $a->getPosition() <=> $b->getPosition();
        });

        $pipelineDto->setActions($actions);

        // get customer's actions
        $customerActions = $this->actionRepository->customerHasActions($customer);

        $form = $this->createForm(PipelineCreateType::class, $pipelineDto, [
            'customerActions' => $customerActions,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->get('submit_add')->isClicked()) {
                // Handle "Add Action" button click
                $data = $form->getData();

                $this->entityManager->persist($customer);

                $newAction = new ActionDto();
                $newAction->setPosition(count($data->getActions()) + 1);

                $actions = $data->getActions();

                $actions[] = $newAction;

                $data->setActions($actions);

                $form = $this->createForm(PipelineCreateType::class, $data, [
                    'customerActions' => $customerActions,
                ]);

                return $this->render('pipeline/pipelineEdit.html.twig', [
                    'form' => $form->createView(),
                    'actions' => $data->getActions(),
                    'pipeline' => $pipeline,
                ]);

            } elseif ($form->get('submit')->isClicked() && $form->isValid()) {

                /** @var PipelineCreateInputDto $pipelineDto */
                $pipelineDto = $form->getData();

                //Set Customer Okay Password Hash
                $customer->setCustomerOkayPassword(
                    $this->passwordHasher->hashPassword(
                        $customer,
                        $pipelineDto->getCustomerOkayPassword()
                    )
                );

                $actions = $pipelineDto->getActions();
                usort($actions, function (ActionDto $a, ActionDto $b) {
                    return $a->getPosition() <=> $b->getPosition();
                });
                $pipelineDto->setActions($actions);

                $actionSequence = [];
                foreach ($actions as $actionDto) {
                    if (!$actionDto->getActionType() || !$actionDto->getInterval()) {
                        continue;
                    }

                    // find Action and update it Interval and fill Actions properties in Pipeline
                    $action = $this->actionRepository->findOneBy(['customer' => $customer, 'actionType' => $actionDto->getActionType()->value]);

                    //on edit set Pipeline fields from First Action(DTO) in sequence
                    if($actionDto->getPosition() == '1'){
                        $pipeline->setActionType($action->getActionType());
                        $pipeline->setActionStatus(ActionStatusEnum::ACTIVATED);
                        $pipeline->setPipelineStatus(ActionStatusEnum::ACTIVATED);
                    }
                    $action->setTimeInterval($actionDto->getInterval());
                    $this->entityManager->persist($action);

                    $actionSequence[] = [
                        'position' => $actionDto->getPosition(),
                        'actionType' => $actionDto->getActionType()->value,
                        'interval' => $actionDto->getInterval()->value,
                    ];
                }

                $pipeline->setActionSequence($actionSequence);

                $this->entityManager->flush();

                $this->addFlash('success', $this->translator->trans('errors.flash.pipeline_saved'));

                return $this->redirectToRoute('pipeline_edit', ['pipelineId' => $pipeline->getId()]);
            }
        }

        return $this->render('pipeline/pipelineEdit.html.twig', [
            'form' => $form->createView(),
            'actions' => $actions,
            'pipeline' => $pipeline,
        ]);
    }
}

