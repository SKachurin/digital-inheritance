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

        switch ($contactType) {
            case ContactTypeEnum::EMAIL:
                if ($this->actionRepository->customerVerifiedSecondEmail($customer)){
                    $this->createSingleAction($contact, ActionTypeEnum::EMAIL_SEND_2);
                    break;
                }
                $this->createSingleAction($contact, ActionTypeEnum::EMAIL_SEND);
                break;

            case ContactTypeEnum::MESSENGER:
            case ContactTypeEnum::PHONE:
                if ($this->actionRepository->customerVerifiedSecondPhone($customer)) {
                    $this->createSingleAction($contact, ActionTypeEnum::MESSENGER_SEND_2);
                    break;
                }
                $this->createSingleAction($contact, ActionTypeEnum::MESSENGER_SEND);
                break;

            case ContactTypeEnum::SOCIAL:
                $this->createSingleAction($contact, ActionTypeEnum::SOCIAL_CHECK);
                $this->createSingleAction($contact, ActionTypeEnum::SOCIAL_SEND);
                break;
        }

        $this->entityManager->flush();
    }

    /**
     * a method to DRY
     */
    private function createSingleAction($contact, ActionTypeEnum $actionType): void
    {
        $actionCreateDto = new ActionCreateDto(
            $contact,
            $actionType,
            IntervalEnum::NOT_SET,
            'active'   // or 'pending', 'done', etc.
        );

        $action = new Action(
            $actionCreateDto->getCustomer(),
            $actionCreateDto->getActionType()
        );

        $action->setContact($contact);
        $action->setTimeInterval($actionCreateDto->getInterval());
        $action->setStatus($actionCreateDto->getStatus());

        $this->entityManager->persist($action);
    }
}
