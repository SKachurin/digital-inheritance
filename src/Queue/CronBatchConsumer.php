<?php

namespace App\Queue;

use App\Entity\Action;
use App\Entity\Contact;
use App\Entity\Pipeline;
use App\Enum\ActionStatusEnum;
use App\Enum\ActionTypeEnum;
use App\Enum\ContactTypeEnum;
use App\Enum\CustomerPaymentStatusEnum;
use App\Enum\IntervalEnum;
use App\Message\CronBatchMessage;
use App\Repository\ActionRepository;
use App\Repository\ContactRepository;
use App\Repository\CustomerRepository;
use App\Repository\PipelineRepository;
use App\Service\BeneficiaryNotificationService;
use App\Service\SendEmailService;
use App\Service\SendSocialService;
use App\Service\SendWhatsAppService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Consumer processes the chunk of customers
 */
#[AsMessageHandler]
class CronBatchConsumer
{
    public function __construct(
        private CustomerRepository             $customerRepository,
        private PipelineRepository             $pipelineRepository,
        private EntityManagerInterface         $entityManager,
        private ActionRepository               $actionRepository,
        private ContactRepository              $contactRepository,
        private SendSocialService              $socialService,
        private SendWhatsAppService            $whatsAppService,
        private SendEmailService               $emailService,
        private BeneficiaryNotificationService $beneficiaryNotificationService,
        private LoggerInterface                $logger,
        private TranslatorInterface            $translator
    ) {}

    /**
     * @throws \Exception
     * @throws TransportExceptionInterface
     */
    public function __invoke(CronBatchMessage $message): void
    {
        $customerIds = $message->getCustomerIds();

//        $this->logger->error('3 CronBatchConsumer == $customerIds', [
//            'customerIds' => $customerIds
//        ]);

        //Load customers
//        $customers = $this->customerRepository->findBy([
//            'id' => $customerIds,
//            'customerPaymentStatus' => CustomerPaymentStatusEnum::PAID->value
//        ]);
        $customers = [];
        foreach ($customerIds as $customerId) {
            $customer = $this->customerRepository->findBy([
                'id' => $customerId,
                'customerPaymentStatus' => CustomerPaymentStatusEnum::PAID->value
            ]);
            $customers = array_merge($customers, $customer);
        }

//        $this->logger->error('3-1 CronBatchConsumer', [
//            '$customers' => $customers
//        ]);

        //Process each customer's pipeline
        foreach ($customers as $customer) {

            $pipeline = $this->pipelineRepository->findOneBy(['customer' => $customer]);

//            if ($pipeline) {
//
//                $this->logger->error('3-3 CronBatchConsumer', [
//                    '$pipeline->getPipelineStatus()' => $pipeline->getPipelineStatus()
//                ]);
//            }
//            else {
//                $this->logger->error('Pipeline is NULL for customer', [
//                    'customerId' => $customer->getId()
//                ]);
//                continue;
//            }

            if ($pipeline && $pipeline->getPipelineStatus() === ActionStatusEnum::ACTIVATED) {

                $lang = $customer?->getLang() ?? 'en';
                $this->processPipeline($pipeline, $lang);
            }
        }

        //After all updates
        $this->entityManager->flush();

        //free memory between messages
        $this->entityManager->clear();
    }

    /**
     * @throws \Exception
     * @throws TransportExceptionInterface
     */
    private function processPipeline(Pipeline $pipeline, string $lang): void
    {
        $activeAction = $pipeline->getActionType();
        $activeActionStatus = $pipeline->getActionStatus();
        $actionSequence = $pipeline->getActionSequence();
        $lastUpdate = $pipeline->getUpdatedAt();


//        $this->logger->error('3.1 processPipeline == Checking Pipeline', [
//            'pipelineId' => $pipeline->getId(),
//            'actionType' => $activeAction,
//            'actionStatus' => $activeActionStatus
//        ]);

        if (empty($actionSequence)) {
//            $this->logger->error(sprintf('No action sequence for pipeline ID %d', $pipeline->getId()));
            return;
        }

        // Sort actions by position (just to be safe)
        usort($actionSequence, fn($a, $b) => (int)$a['position'] <=> (int)$b['position']);

//        $this->logger->error('3.1-0 processPipeline', [
//            '$actionSequence' => $actionSequence
//        ]);

        // Find active action
        foreach ($actionSequence as $actionData) {

            if ($actionData['actionType'] === $activeAction->value) {

                //it has Interval
                $intervalValue = $actionData['interval'] ?? null;

//                $this->logger->error('3.1-1 processPipeline', [
//                    '$intervalValue' => $intervalValue
//                ]);

                if (!$intervalValue) {
//                    $this->logger->error(sprintf('No action sequence for pipeline ID %d', $pipeline->getId()));
                    break; // major error - we don't have interval for this Action          //TODO ADMIN_ALERT Service
                }

                //calculate time
                $nextActionTime = $this->calculateNextActionTime($lastUpdate, $intervalValue);
                $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

//                $this->logger->error('3.1-2 processPipeline', [
//                    '$now' => $now,
//                    '$nextActionTime' => $nextActionTime,
//                    '$activeAction' => $activeAction,
//                    '$actionData[actionType] ' => $actionData['actionType']
//                ]);

                // Branch based on action type:
                if ($activeAction->value === ActionTypeEnum::SOCIAL_CHECK->value) {
                    // SOCIAL_CHECK: Wait until the interval is reached && Execute if active
                    if ($now >= $nextActionTime && $activeActionStatus === ActionStatusEnum::ACTIVATED) {

                        $result = $this->executeActiveAction($pipeline, $actionData, $now, $lang);
                        $pipeline->setActionStatus($result);

                        //return; // next move  - NOW
                    }
                } else {
                    // Messenger/Email: Execute immediately if active
                    if ($activeActionStatus === ActionStatusEnum::ACTIVATED) {

                        $result = $this->executeActiveAction($pipeline, $actionData, $now, $lang);
                        $pipeline->setActionStatus($result);

                        return; // next move  - with next run
                    }
                }

                // After that, regardless of type, if the interval is reached...
                if ($now >= $nextActionTime) {
                    // If the pipeline is no longer in ACTIVATED status (for example, it’s PENDING),
                    // process the pending action.
                    if ($activeActionStatus !== ActionStatusEnum::ACTIVATED && $activeActionStatus !== ActionStatusEnum::FAIL) {
                        $this->processPendingAction($pipeline, $actionData, $now);
                        return;
                    }

                    // Otherwise, re-run executeActiveAction (or continue with further processing)
//                    $result = $this->executeActiveAction($pipeline, $actionData, $now);
                    if (!isset($result)) {

                        $result = $activeActionStatus;

                    } else {

                        switch ($result) {
                            case ActionStatusEnum::ACTIVATED:
                                // Nothing to change
                                break;
                            case ActionStatusEnum::FAIL:
                                $this->processFailedAction($pipeline, $actionData, $now);
                                break;
                            case ActionStatusEnum::PENDING:
                                // Set state to pending
                                $pipeline->setActionStatus(ActionStatusEnum::PENDING);
                                break;
                            case ActionStatusEnum::SUCCESS:
                                // Restore the pipeline to the initial state (or move to the next step)
                                //it's not working cos we don't set ActionStatusEnum::SUCCESS anywhere

//                                break;
                        }
                    }
                }
            }
        }


        $this->logger->error('3.5 CronBatchConsumer == processPipeline()', [
            '$pipeline->getActionStatus()' => $pipeline->getActionStatus()
        ]);
    }

    private function calculateNextActionTime(\DateTimeImmutable $lastUpdate, string $intervalValue): \DateTimeImmutable
    {
        $intervalEnum = IntervalEnum::fromString($intervalValue);
        $interval = $intervalEnum->toDateInterval();

        return $lastUpdate->add($interval);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws \SodiumException
     */
    private function executeActiveAction(Pipeline $pipeline, array $actionData, \DateTimeImmutable $now, string $lang): ActionStatusEnum
    {
        $actionType = ActionTypeEnum::tryFrom($actionData['actionType']);

        $message = $this->translator->trans('messages.check_message', [], 'messages', $lang);

        // Action & Contact
        [$action, $contact] = $this->retrieveActionAndContact($pipeline, $actionType);

//        $this->logger->error('3.2 executeActiveAction == Checking Action Type', [
//            'pipelineId' => $pipeline->getId(),
//            'actionType' => $actionType->value,
//        ]);

        return match ($actionType) {
            ActionTypeEnum::SOCIAL_CHECK => $this->sendSocialCheck($actionData, $now, $contact),
            ActionTypeEnum::MESSENGER_SEND,
            ActionTypeEnum::MESSENGER_SEND_2 => $this->sendMessenger($action, $contact, $message),
            ActionTypeEnum::EMAIL_SEND,
            ActionTypeEnum::EMAIL_SEND_2 => $this->sendEmail($action, $contact, $message),
            default => throw new \Exception("Unsupported action type: $actionType->value"),
        };
    }

    /**
     * @throws \SodiumException
     * @throws \Exception
     */
    private function processPendingAction(Pipeline $pipeline, array $actionData, \DateTimeImmutable $now): void
    {
        $actionType = ActionTypeEnum::tryFrom($actionData['actionType']);


        switch ($actionType) {

            case ActionTypeEnum::SOCIAL_CHECK:
                // this case is not possible

            case ActionTypeEnum::MESSENGER_SEND:
            case ActionTypeEnum::MESSENGER_SEND_2:
            case ActionTypeEnum::EMAIL_SEND:
            case ActionTypeEnum::EMAIL_SEND_2:
                //waiting answer with Okay Password -> reset Pipeline to beginning.
                //if time is up, and it's still Pending - it's not good.
                // So:
                $this->processFailedAction($pipeline, $actionData, $now);

                break;

            default:
                throw new \Exception("Unsupported action type: $actionType->value");

        }

//        $this->logger->error('9 processPendingAction');
    }

    /**
     * @throws \Symfony\Component\Mailer\Exception\TransportExceptionInterface
     */
    private function processFailedAction(Pipeline $pipeline, array $actionData, \DateTimeImmutable $now) : void
    {
        $nextActionPosition = $actionData['position'] + 1;
        $actionSequence = $pipeline->getActionSequence();

        // Find the next action in the sequence
        $nextAction = array_filter($actionSequence, fn($a) => $a['position'] === $nextActionPosition);

        if (!empty($nextAction)) {
            $nextAction = reset($nextAction); // Get the first matching action
//            $this->logger->info(sprintf('Setting next action: %s for pipeline ID %d', $nextAction['actionType'], $pipeline->getId()));

            // Update pipeline with next action
            $pipeline->setActionType(ActionTypeEnum::from($nextAction['actionType']));
            $pipeline->setActionStatus(ActionStatusEnum::ACTIVATED);

        } else {

            //pipeline ended - release Envelope to Heir
            $pipeline->setActionStatus(ActionStatusEnum::FAIL);
            $pipeline->setPipelineStatus(ActionStatusEnum::FAIL);

            $this->entityManager->persist($pipeline);
            $this->entityManager->flush();

//            $this->logger->info(sprintf('No next action found for pipeline ID %d', $pipeline->getId()));

            // process Failed Pipeline Function
            $result = $this->processFailedPipeline($pipeline, $now);

            //done
            $result = $result ? 'true' : 'false';
            $this->logger->info(sprintf('Pipeline ID %d ended at %s with result %s', $pipeline->getId(), $now->format('c'), $result));

        }
    }

    /**
     * @throws \Symfony\Component\Mailer\Exception\TransportExceptionInterface
     */
    private function processFailedPipeline(Pipeline $pipeline, \DateTimeImmutable $now) : bool
    {
        $beneficiaries = $pipeline->getCustomer()->getBeneficiary();
        $beneficiary = $beneficiaries[0];
        $contacts = $this->contactRepository->findBy(['beneficiary' => $beneficiary]);

        // logic to send Envelope to the Heir
        return $this->beneficiaryNotificationService->notifyBeneficiary($beneficiary, $contacts, $now);
    }

    /**
     * @throws \RuntimeException if no Action or Contact is found
     */
    private function retrieveActionAndContact(Pipeline $pipeline, ActionTypeEnum $actionType): array
    {
        /** @var Action $action */
        $action = $this->actionRepository->findOneBy([
            'customer' => $pipeline->getCustomer(),
            'actionType' => $actionType
        ]);
        if (!$action) {
            throw new \RuntimeException(sprintf(
                'No Action found for pipeline ID %d and actionType %s',
                $pipeline->getId(),
                $actionType->value
            ));
        }

        $contact = $this->contactRepository->findOneBy(['id' => $action->getContact()->getId()]);
        if (!$contact) {
            throw new \RuntimeException(sprintf(
                'No Contact found (ID: %d) for pipeline ID %d',
                $action->getContact()->getId(),
                $pipeline->getId()
            ));
        }

        return [$action, $contact];
    }

    /**
     * @param array $actionData
     * @param \DateTimeImmutable $now
     * @param Contact $contact
     * @return ActionStatusEnum
     * @throws \SodiumException
     */
    private function sendSocialCheck(array $actionData, \DateTimeImmutable $now, Contact $contact): ActionStatusEnum
    {
        $response = $this->socialService->sendMessageSocial($contact);

        $data = $this->decodeJsonResponse($response);

        // "output" key not found, fallback
        if (!isset($data['output'])) {
            // next try
            return ActionStatusEnum::ACTIVATED;
        }

        $result = $data['output'];
        foreach ($result as $user => $timestamp) {
            try {
                $lastOnlineTime = new \DateTimeImmutable($timestamp, new \DateTimeZone('UTC'));

                // Calculate the maxTime: now - interval
                $interval = IntervalEnum::fromString($actionData['interval'])->toDateInterval();
                $maxAllowedTime = $now->sub($interval);

                if ($lastOnlineTime < $maxAllowedTime) {

                    $this->logger->error('3.2-2 sendSocialCheck()', [
                        '$lastOnlineTime' => $lastOnlineTime,
                        'now' => $now,
                    ]);

                    return ActionStatusEnum::FAIL; // user is offline too long
                }

                // else they're within the allowed time
                return ActionStatusEnum::ACTIVATED;

            } catch (\Exception $e) {
//                $this->logger->info(sprintf(
//                    'Invalid timestamp for user %s: %s',
//                    $user,
//                    $timestamp
//                ));
            }
        }

        //next try
        return ActionStatusEnum::ACTIVATED;
    }

    /**
     * @param Action $action
     * @param Contact $contact
     * @param string $message
     * @return ActionStatusEnum
     * @throws TransportExceptionInterface
     * @throws \SodiumException
     */
    private function sendMessenger(Action $action, Contact $contact, string $message): ActionStatusEnum
    {
//        $this->logger->error('3.3 sendMessenger == Called', [
//            'contactId' => $contact->getId(),
//            'message' => $message
//        ]);

        $response = $this->whatsAppService->sendMessageWhatsApp($contact, $message);

//        $this->logger->error('4 CronBatchConsumer == $response' . $response);

        $data = $this->decodeJsonResponse($response);

        if (isset($data['messageId'])) {
            //success. message sent.
            $action->setChatId($data['chatId']);

            return ActionStatusEnum::PENDING;
        }

        //fail to check  - next try
        return ActionStatusEnum::ACTIVATED;
    }


    /**
     * @param Action $action
     * @param Contact $contact
     * @param string $message
     * @return ActionStatusEnum
     * @throws \SodiumException
     */
    private function sendEmail(Action $action, Contact $contact, string $message): ActionStatusEnum
    {

        $response = $this->emailService->sendMessageEmail($contact, $message);

//        $this->logger->error('4 CronBatchConsumer sendEmail == $response' . $response);

        $data = $this->decodeJsonResponse($response);

        if (isset($data['chatId'])) {
            //success. message sent.
            $action->setChatId($data['chatId']);

            return ActionStatusEnum::PENDING;
        }

        //fail to check  - next try
        return ActionStatusEnum::ACTIVATED;
    }

    private function decodeJsonResponse(JsonResponse $response): array
    {
        $data = json_decode($response->getContent(), true);
        return is_array($data) ? $data : [];
    }

}
