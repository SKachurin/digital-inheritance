<?php
namespace App\Controller;


use App\CommandHandler\Customer\Compare\CustomerCompareOutputDto1;
use App\Form\Type\DemonstrationType1;
use App\Message\CustomerWithContactsMessage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use App\Repository\CustomerRepository;


class CustomerCompareController extends AbstractController
{
    private MessageBusInterface $commandBus;
    private CustomerRepository $repository;


    public function __construct(MessageBusInterface $commandBus, CustomerRepository $repository)
    {
        $this->commandBus = $commandBus;
        $this->repository = $repository;
    }


    public function compare(): Response
    {

        $result = $this->repository->findLastWithContacts();

        if ($result) {

            if (!is_array($result)) {
                throw new \UnexpectedValueException('Expected an array from findLastWithContacts');
            }

//            [$customer, $contacts] = $result; // Destructuring the array
            $customer = $result[0];
            $contacts = $result[1];

            $message = new CustomerWithContactsMessage($customer, $contacts);
            $envelope = $this->commandBus->dispatch($message);
            $handledStamp = $envelope->last(HandledStamp::class);

            if (!$handledStamp) {
                throw new UnprocessableEntityHttpException('500 internal error (CommandBus not responding).');
            }

//            [$dto1, $dto2] = $handledStamp->getResult();
            $handledResult = $handledStamp->getResult();

            if (!is_array($handledResult) || count($handledResult) < 2) {
                throw new \UnexpectedValueException('Expected an array with at least two elements from handled result');
            }

            /** @var CustomerCompareOutputDto1 $dto1 */
            $dto1 = $handledResult[0];
            /** @var CustomerCompareOutputDto1 $dto2 */
            $dto2 = $handledResult[1];

            $form1 = $this->createForm(DemonstrationType1::class, $dto1);
            $form2 = $this->createForm(DemonstrationType1::class, $dto2);


            return $this->render('user/compare.html.twig', [
                'form1' => $form1->createView(),
                'form2' => $form2->createView(),
            ]);

        } else {

            return $this->render('user/created.html.twig');
        }

    }
}