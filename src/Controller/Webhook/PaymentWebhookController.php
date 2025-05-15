<?php

declare(strict_types=1);

namespace App\Controller\Webhook;

use App\Entity\Transaction;
use App\Enum\CustomerPaymentStatusEnum;
use App\Repository\CustomerRepository;
use App\Service\PlanPriceResolver;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PaymentWebhookController extends AbstractController
{
    public function __construct(
        private string                   $apiKey,
        private EntityManagerInterface   $em,
        private CustomerRepository       $customerRepository,
        private PlanPriceResolver        $planPriceResolver,
        private readonly LoggerInterface $logger,
    )
    {
    }
    public function __invoke(Request $request): Response
    {
        if (!$request->headers->contains('content-type', 'application/x-www-form-urlencoded')) {
            //TODO send alert to admin
            return new Response('Unsupported content type', 415);
        }

        $data = $request->request->all();

        $this->logger->error('CryptoCloud webhook received', [
            'raw_body' => $request->getContent(),
            'decoded_payload' => $data,
            'headers' => $request->headers->all(),
        ]);

        if (!isset($data['status']) || $data['status'] !== 'paid') {
            return new Response('Ignored', 200);
        }

        $invoiceId = $data['invoice_id'] ?? null;
        $orderId = $data['order_id'] ?? null;
        $email = $data['email'] ?? null;
        $amount = $data['amount'] ?? null;
        $token = $data['token'] ?? null;

        // Basic check
        if (
            !$token ||
            !$invoiceId ||
            !$orderId ||
            !$amount ||
            !is_numeric($amount) ||
            $amount <= 0
        ) {
            return new Response('Invalid payload', 400);
        }

        // Strip possible "b" prefix
        if (str_starts_with($token, 'b') && substr_count($token, '.') === 2) {
            $token = substr($token, 1);
        }

        if (!$this->isValidJwt($token, $this->apiKey)) {
            return new Response('Invalid JWT signature', 403);
        }

        $decoded = $this->decodeJwtPayload($token);

        if (($decoded['invoice_id'] ?? null) !== $invoiceId) {
            return new Response('JWT payload mismatch', 403);
        }

        $parts = explode('/', $decoded['order_id']);
        $customerId = $parts[0] ?? null;
        $plan = $parts[1] ?? null;
        if (!$customerId || !$customer = $this->customerRepository->find((int) $customerId)) {
            return new Response('Customer not found', 404);
        }

        // Sum check
        $pricePerMonth = $this->planPriceResolver->getPricePerMonth($plan);

        if (!$pricePerMonth || $amount < $pricePerMonth) {

            $this->logger->error('Payment amount too low', [
                'amount' => $amount,
                'plan' => $plan,
                'pricePerMonth' => $pricePerMonth,
            ]);                                         // TODO message Admin

            return new Response('Amount too low for selected plan', 400);
        }

        $now = new \DateTimeImmutable();
        $startDate = $now;

        $latestTransaction = $customer->getTransactions()->first();
        if ($latestTransaction && $latestTransaction->getPaidUntil() > $now) {
            $startDate = $latestTransaction->getPaidUntil();
        }

        $monthsPaid = (int)floor($amount / $pricePerMonth);
        $daysToAdd = $monthsPaid * 31;
        $paidUntil = $startDate->modify("+{$daysToAdd} days");

        $transaction = (new Transaction($customer))
            ->setAmount((float) $amount)
            ->setCurrency($data['currency'] ?? 'USD')
            ->setPaymentMethod('CryptoCloud')
            ->setStatus('paid')
            ->setPlan($plan)
            ->setPaidUntil($paidUntil);

        $customer->setCustomerPaymentStatus(CustomerPaymentStatusEnum::PAID);

        $this->em->persist($transaction);
        $this->em->persist($customer);

        $this->em->flush();

        return new Response('OK', 200);
    }


    private function isValidJwt(string $jwt, string $secret): bool
    {
        [$header64, $payload64, $signature] = explode('.', $jwt);
        $data = $header64 . '.' . $payload64;

        $expected = rtrim(strtr(base64_encode(
            hash_hmac('sha256', $data, $secret, true)
        ), '+/', '-_'), '=');

        return hash_equals($expected, $signature);
    }

    private function decodeJwtPayload(string $jwt): array
    {
        [, $payload64, ] = explode('.', $jwt);
        return json_decode(base64_decode(strtr($payload64, '-_', '+/')), true);
    }
}