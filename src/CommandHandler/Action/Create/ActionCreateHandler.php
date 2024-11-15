<?php

namespace App\CommandHandler\Action\Create;

use App\Command\ActionCreateCommand;
use App\Entity\Action;
use App\Enum\ActionTypeEnum;
use App\Enum\ContactTypeEnum;
use App\Enum\IntervalEnum;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\ActionRepository;

class ActionCreateHandler
{
    private EntityManagerInterface $entityManager;
    private ActionRepository $actionRepository;

    public function __construct(EntityManagerInterface $entityManager, ActionRepository $actionRepository)
    {
        $this->entityManager = $entityManager;
        $this->actionRepository = $actionRepository;
    }

    public function __invoke(ActionCreateCommand $command): void
    {
        $contact = $command->getContact();
        $contactType = $contact->getContactTypeEnum();
        $customer = $contact->getCustomer();
        $actionType = 'none';

        switch ($contactType) {
            case ContactTypeEnum::EMAIL:
                if ($this->actionRepository->customerVerifiedSecondEmail($customer)){
                    $actionType = ActionTypeEnum::EMAIL_SEND_2;
                    break;
                }
                $actionType = ActionTypeEnum::EMAIL_SEND;
                break;
            case ContactTypeEnum::MESSENGER:
            case ContactTypeEnum::PHONE:
                if ($this->actionRepository->customerVerifiedSecondPhone($customer)) {
                    $actionType = ActionTypeEnum::MESSENGER_SEND_2;
                    break;
                }
                $actionType = ActionTypeEnum::MESSENGER_SEND;
                break;
            case ContactTypeEnum::SOCIAL:
                $actionType = ActionTypeEnum::SOCIAL_CHECK;
                break;
        }

        $actionCreateDto = new ActionCreateDto(
            $contact,
            $actionType,
            IntervalEnum::NOT_SET,
            'active',                    // Status is 'active', 'pending', 'done'
        );

        $action = new Action(
            $actionCreateDto->getCustomer(),
            $actionCreateDto->getActionType()
        );
        $action->setTimeInterval($actionCreateDto->getInterval());
        $action->setStatus($actionCreateDto->getStatus());

        $this->entityManager->persist($action);
        $this->entityManager->flush();
    }
}
