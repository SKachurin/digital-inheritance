<?php
namespace App\Middleware;

use App\Queue\Doctrine\Customer\CustomerCreatedMessage;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Envelope;
use App\Repository\CustomerRepository;
use Symfony\Component\Messenger\Stamp\HandledStamp;

class CheckCustomerExistenceMiddleware implements MiddlewareInterface
{
    private CustomerRepository $customerRepository;

    public function __construct(CustomerRepository $customerRepository)
    {
        $this->customerRepository = $customerRepository;
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $message = $envelope->getMessage();

        if ($message instanceof CustomerCreatedMessage) {
            $customerEmail = $message->getCustomerCreateInputDto()->getCustomerEmail();
            if ($this->customerRepository->findOneBy(['customerEmail' => $customerEmail])) {

                // Mark as handled, stop further processing
                return $envelope->with(new HandledStamp(null, 'duplicate_handled_successfully'));
            }
        }

        // Proceed with normal processing if no duplicate is found
        return $stack->next()->handle($envelope, $stack);
    }
}
