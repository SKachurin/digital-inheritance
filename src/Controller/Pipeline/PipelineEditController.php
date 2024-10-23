<?php

namespace App\Controller\Pipeline;

use App\CommandHandler\Pipeline\Create\ActionDto;
use App\CommandHandler\Pipeline\Create\PipelineCreateInputDto;
use App\Entity\Customer;
use App\Enum\ActionTypeEnum;
use App\Enum\IntervalEnum;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
//use App\Entity\Pipeline;
//use App\Enum\ActionStatusEnum;
use App\Form\Type\PipelineCreateType;
use App\Repository\ActionRepository;
use App\Repository\PipelineRepository;
use Doctrine\ORM\EntityManagerInterface;
//use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
//use Symfony\Component\Messenger\MessageBusInterface;
//use Symfony\Component\Messenger\Stamp\HandledStamp;

class PipelineEditController extends AbstractController
{
    private PipelineRepository $pipelineRepository;
    private ActionRepository $actionRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(
        PipelineRepository $pipelineRepository,
        ActionRepository $actionRepository,
        EntityManagerInterface $entityManager
    )
    {
        $this->pipelineRepository = $pipelineRepository;
        $this->actionRepository = $actionRepository;
        $this->entityManager = $entityManager;
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

        $pipelineDto = new PipelineCreateInputDto($customer);

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
                    $actionSequence[] = [
                        'position' => $actionDto->getPosition(),
                        'actionType' => $actionDto->getActionType()->value,
                        'interval' => $actionDto->getInterval()->value,
                    ];
                }

                $pipeline->setActionSequence($actionSequence);

                $this->entityManager->flush();

                return $this->redirectToRoute('pipeline_edit', ['pipelineId' => $pipeline->getId()]);
            }
        }

        return $this->render('pipeline/pipelineEdit.html.twig', [
            'form' => $form->createView(),
            'actions' => $actions,
            'pipeline' => $pipeline,
        ]);
    }

//    public function addAction(int $pipelineId): Response
//    {
//        $pipeline = $this->pipelineRepository->find($pipelineId);
//
//        if (!$pipeline) {
//            throw $this->createNotFoundException('Pipeline not found.');
//        }
//
//        $customer = $this->getUser();
//
//        if (!$customer instanceof Customer || $pipeline->getCustomer() !== $customer) {
//            throw $this->createAccessDeniedException('You do not have access to this pipeline.');
//        }
//
//        // Get the existing action sequence
//        $actionSequence = $pipeline->getActionSequence();
//
//        // Add a new empty action
//        $actionSequence[] = [
//            'position' => count($actionSequence) + 1,
//            'actionType' => null,
//            'interval' => null,
//        ];
//
//        // Update the pipeline
//        $pipeline->setActionSequence($actionSequence);
//
//        $this->entityManager->flush();
//
//        // Redirect back to the edit route
//        return $this->redirectToRoute('pipeline_edit', ['pipelineId' => $pipeline->getId()]);
//    }
}

