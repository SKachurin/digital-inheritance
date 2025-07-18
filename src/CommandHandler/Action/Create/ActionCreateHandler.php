<?php

namespace App\CommandHandler\Action\Create;

use App\Command\ActionCreateCommand;
use App\Entity\Action;
use App\Repository\ContactRepository;
use App\Enum\ActionTypeEnum;
use App\Enum\ContactTypeEnum;
use App\Enum\IntervalEnum;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\ActionRepository;

class ActionCreateHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ActionRepository       $actionRepository,
        private ContactRepository      $contactRepository
    )
    {
    }

    public function __invoke(ActionCreateCommand $command): void
    {
        $contact = $this->contactRepository->find($command->getContactId());

        if (!$contact) {
            return;
        }
        $contactType = $contact->getContactTypeEnum();
        $customer = $contact->getCustomer();

        //delete previous Action for Contact
        $oldAction = $this->actionRepository->findOneBy(['customer' => $customer, 'contact' => $contact]);

        if ($oldAction) {
            $this->entityManager->remove($oldAction);
        }

        switch ($contactType) {
            case ContactTypeEnum::EMAIL->value:
                if ($this->actionRepository->customerVerifiedSecondEmail($customer)){
                    $this->createSingleAction($contact, ActionTypeEnum::EMAIL_SEND_2);
                    break;
                }
                $this->createSingleAction($contact, ActionTypeEnum::EMAIL_SEND);
                break;

            case ContactTypeEnum::MESSENGER->value:
            case ContactTypeEnum::PHONE->value:
                if ($this->actionRepository->customerVerifiedSecondPhone($customer)) {
                    $this->createSingleAction($contact, ActionTypeEnum::MESSENGER_SEND_2);
                    break;
                }
                $this->createSingleAction($contact, ActionTypeEnum::MESSENGER_SEND);
                break;

            case ContactTypeEnum::SOCIAL->value:
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
