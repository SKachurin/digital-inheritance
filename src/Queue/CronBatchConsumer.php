<?php

namespace App\Queue;

use App\Entity\Action;
use App\Entity\Contact;
use App\Entity\Pipeline;
use App\Enum\ActionStatusEnum;
use App\Enum\ActionTypeEnum;
use App\Enum\CustomerPaymentStatusEnum;
use App\Enum\IntervalEnum;
use App\Message\CronBatchMessage;
use App\Repository\ActionRepository;
use App\Repository\ContactRepository;
use App\Repository\CustomerRepository;
use App\Repository\PipelineRepository;
use App\Service\SendSocialService;
use App\Service\SendWhatsAppService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Consumer processes the chunk of customers
 */
#[AsMessageHandler]
class CronBatchConsumer
{
    public function __construct(
        private CustomerRepository $customerRepository,
        private PipelineRepository $pipelineRepository,
        private EntityManagerInterface $entityManager,
        private ActionRepository $actionRepository,
        private ContactRepository $contactRepository,
        private SendSocialService  $socialService,
        private SendWhatsAppService $whatsAppService,
        private LoggerInterface $logger
    ) {}

    /**
     * @throws \Exception
     * @throws TransportExceptionInterface
     */
    public function __invoke(CronBatchMessage $message): void
    {
        $customerIds = $message->getCustomerIds();

        $this->logger->error('3 CronBatchConsumer == $customerIds');

        //Load customers
        $customers = $this->customerRepository->findBy([
            'id' => $customerIds,
            'customerPaymentStatus' => CustomerPaymentStatusEnum::PAID->value
        ]);

        //Process each customer's pipeline
        foreach ($customers as $customer) {

            $pipeline = $this->pipelineRepository->findOneBy(['customer' => $customer]);

            if ($pipeline && $pipeline->getPipelineStatus() === ActionStatusEnum::ACTIVATED->value) {
                // Reuse your cronService logic
                $this->processPipeline($pipeline);
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
    private function processPipeline(Pipeline $pipeline): void
    {
        $activeAction = $pipeline->getActionType();
        $activeActionStatus = $pipeline->getActionStatus();
        $actionSequence = $pipeline->getActionSequence();
        $lastUpdate = $pipeline->getUpdatedAt();

        if (empty($actionSequence)) {
            $this->logger->info(sprintf('No action sequence for pipeline ID %d', $pipeline->getId()));
            return;
        }

        // Sort actions by position (just to be safe)
        usort($actionSequence, fn($a, $b) => (int)$a['position'] <=> (int)$b['position']);

        // Find active action
        foreach ($actionSequence as $actionData) {

            if ($actionData['actionType'] == $activeAction) {

                //it has Interval
                $intervalValue = $actionData['interval'] ?? null;
                if (!$intervalValue) {
                    $this->logger->info(sprintf('No action sequence for pipeline ID %d', $pipeline->getId()));
                    break; // major error - we don't have interval for this Action          //TODO ADMIN_ALERT Service
                }

                //calculate time
                $nextActionTime = $this->calculateNextActionTime($lastUpdate, $intervalValue);
                $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

                //if time is up
                if ($now >= $nextActionTime) {

                    //process all other statuses of ActionStatusEnum - it's only ActionStatusEnum::PENDING:
                    // other statuses handled in executeActiveAction()
                    if ($activeActionStatus !== ActionStatusEnum::ACTIVATED) {

                        $this->processPendingAction($pipeline, $actionData, $now);

                        return;
                    }

                    //process Active status
                    $result = $this->executeActiveAction($pipeline, $actionData, $now);

                    switch ($result) {
                        case ActionStatusEnum::ACTIVATED:
                            // basically 'Okay for now' -- do nothing

                            break;

                        case ActionStatusEnum::FAIL:     // already 'not Okay'

                            $this->processFailedAction($pipeline, $actionData, $now);
                            break;

                        case ActionStatusEnum::PENDING:
                            // Message was sent to Contact and system waiting for response
                            $pipeline->setActionStatus(ActionStatusEnum::PENDING);

                            break;

                        case ActionStatusEnum::SUCCESS:
                            // restoring initial state of the Pipeline

                            $firstAction = array_filter($actionSequence, fn($a) => $a['position'] === 1);

                            $pipeline->setActionType($firstAction['actionType']);
                            $pipeline->setActionStatus(ActionStatusEnum::ACTIVATED);
                    }
                }
                // we are processing only One Action per client! Yes?
                break;
            }

        }
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
    private function executeActiveAction(Pipeline $pipeline, array $actionData, \DateTimeImmutable $now): ActionStatusEnum
    {
        $actionType = ActionTypeEnum::tryFrom($actionData['actionType']);
        $message = 'Hey! Are you okay?';    //TODO Transl

        // Action & Contact
        [$action, $contact] = $this->retrieveActionAndContact($pipeline, $actionType);

        return match ($actionType) {
            ActionTypeEnum::SOCIAL_CHECK => $this->sendSocialCheck($actionData, $now, $contact),
            ActionTypeEnum::MESSENGER_SEND,
            ActionTypeEnum::MESSENGER_SEND_2 => $this->sendMessenger($action, $contact, $message),
            ActionTypeEnum::EMAIL_SEND,
            ActionTypeEnum::EMAIL_SEND_2 => ActionStatusEnum::PENDING, //TODO send message with token - token lasts for Interval - if token correct - redirect to password enter page
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
    }

    private function processFailedAction(Pipeline $pipeline, array $actionData, \DateTimeImmutable $now) : void
    {
        $nextActionPosition = $actionData['position'] + 1;
        $actionSequence = $pipeline->getActionSequence();

        // Find the next action in the sequence
        $nextAction = array_filter($actionSequence, fn($a) => $a['position'] === $nextActionPosition);

        if (!empty($nextAction)) {
            $nextAction = reset($nextAction); // Get the first matching action
            $this->logger->info(sprintf('Setting next action: %s for pipeline ID %d', $nextAction['actionType'], $pipeline->getId()));

            // Update pipeline with next action
            $pipeline->setActionType($nextAction['actionType']);
            $pipeline->setActionStatus(ActionStatusEnum::ACTIVATED);

        } else {

            //pipeline ended - release Envelope to Heir
            $pipeline->setActionStatus(ActionStatusEnum::FAIL);
            $this->logger->info(sprintf('No next action found for pipeline ID %d', $pipeline->getId()));
            $pipeline->setPipelineStatus(ActionStatusEnum::FAIL);

            // process Failed Pipeline Function?
            $result = $this->processFailedPipeline($pipeline, $actionData, $now);

            if (!empty($result)) {
                //done
                $this->logger->info(sprintf('Pipeline ID %d ended at %s',$pipeline->getId(), $now->format('c')));
            }
        }
    }

    private function processFailedPipeline(Pipeline $pipeline, mixed $actionData, \DateTimeImmutable $now) : bool
    {
        // logic to send Envelope to the Heir

        return true;
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
                    return ActionStatusEnum::FAIL; // user is offline too long
                }

                // else they're within the allowed time
                return ActionStatusEnum::ACTIVATED;

            } catch (\Exception $e) {
                $this->logger->info(sprintf(
                    'Invalid timestamp for user %s: %s',
                    $user,
                    $timestamp
                ));
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
        $response = $this->whatsAppService->sendMessageWhatsApp($contact, $message);

        $this->logger->error('4 CronBatchConsumer == $response' . $response);

        $data = $this->decodeJsonResponse($response);

        if (isset($data['messageId'])) {
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
