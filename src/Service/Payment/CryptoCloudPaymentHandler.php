<?php

declare(strict_types=1);

namespace App\Service\Payment;

use App\Entity\Transaction;
use App\Enum\CustomerPaymentStatusEnum;
use App\Repository\CustomerRepository;
use App\Service\PlanPriceResolver;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Psr\Log\LoggerInterface;

class CryptoCloudPaymentHandler
{
    public function __construct(
        private readonly CryptoCloudInvoiceVerifier $verifier,
        private readonly CustomerRepository         $customerRepository,
        private readonly PlanPriceResolver          $planPriceResolver,
        private readonly EntityManagerInterface     $em,
        private readonly string                     $secretKey,
        private readonly LoggerInterface            $logger,
    )
    {
    }

    public function handleWebhook(array $data): Response
    {
        $token = $data['token'];

        // Checking key
        $tokenParts = explode('.', $token);

        if (count($tokenParts) !== 3) {
            $this->logger->error('Malformed JWT token', ['token' => $token]);
            return new Response('Invalid token format', 403);
        }

        $header = $tokenParts[0];
        $payload = $tokenParts[1];
        $actualSignature = $tokenParts[2];

        $signedData = "$header.$payload";
        $rawSignature = hash_hmac('sha256', $signedData, $this->secretKey, true);
        $expectedSignature = rtrim(strtr(base64_encode($rawSignature), '+/', '-_'), '=');

        if (!hash_equals($expectedSignature, $actualSignature)) {
            $this->logger->error('token secret error');
            return new Response('Invalid token secret', 403);
        }

        // Checking $uuid from 2 sources
        $payloadBase64 = $tokenParts[1] ?? '';
        $payloadJson = base64_decode($payloadBase64);
        $payload = json_decode($payloadJson, true);

        $uuidHidden = $payload['id'] ?? null;

        $uuid = $data['invoice_id'] ?? null;

        if ($uuid !== $uuidHidden) {
            $this->logger->warning('UUID mismatch in webhook', [
                'uuid_from_postback' => $uuid,
                'uuid_hidden' => $uuidHidden,
                'token' => $token,
            ]);
            return new Response('UUID mismatch', 400);
        }

        // request $invoice data form vendor Again
        $invoice = $this->verifier->verify($uuid);

        if (empty($invoice)) {
            $this->logger->error('Missing order data 1');
            return new Response('Missing order data', 400);
        }

        $orderId = $data['order_id'] ?? null;
        $amount = $invoice['received_usd'];
        $currency = 'USD';

        $parts = explode('/', $orderId);
        $customerId = str_replace('order_', '', $parts[0]);
        $plan = $parts[1] ?? null;

        if (!$customerId || !$plan || !$amount) {
            $this->logger->error('Missing order data 2');
            return new Response('Missing order data', 400);
        }

        $customer = $this->customerRepository->find((int) $customerId);
        if (!$customer) {
            return new Response('Customer not found', 404);
        }

        $pricePerMonth = $this->planPriceResolver->getPricePerMonth($plan);

        if (!$pricePerMonth || $amount <= 0) {
            $this->logger->error('Invalid plan price or zero payment', [
                'plan' => $plan,
                'amount' => $amount,
            ]);
            return new Response('Invalid amount', 400);
        }

        // billing with days
        $monthsPaid = 0;
        if ( $amount < $pricePerMonth) {
            $daysPaid = (int) floor(($amount / ($pricePerMonth / 31)));

            if ($daysPaid < 1) {
                $this->logger->error('Amount too low to grant even one day', [
                    'pricePerMonth' => $pricePerMonth,
                    'amount' => $amount,
                ]);
                return new Response('Amount too low', 400);
            }
        } else {
            $monthsPaid = (int)floor($amount / $pricePerMonth);
            $amountLeft = $amount - ($monthsPaid * $pricePerMonth);
            $daysPaid = (int) floor(($amountLeft / ($pricePerMonth / 31)));
        }

        $now = new \DateTimeImmutable();
        $startDate = $now;

        $latestTransaction = $customer->getTransactions()->first();
        if ($latestTransaction && $latestTransaction->getPaidUntil() > $now) {
            $startDate = $latestTransaction->getPaidUntil();
        }

        $daysToAdd = ($monthsPaid * 31) + $daysPaid;
        $paidUntil = $startDate->modify("+{$daysToAdd} days");

        $transaction = (new Transaction($customer))
            ->setAmount((float) $amount)
            ->setCurrency($currency)
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

}
