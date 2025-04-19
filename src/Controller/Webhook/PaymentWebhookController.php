<?php

declare(strict_types=1);

namespace App\Controller\Webhook;

use App\Entity\Transaction;
use App\Repository\CustomerRepository;
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
        private readonly LoggerInterface $logger,
    )
    {
    }
    public function __invoke(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        $this->logger->info('CryptoCloud webhook', ['payload' => $data]);

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

        $transaction = (new Transaction($customer))
            ->setAmount((float) $amount)
            ->setCurrency($data['currency'] ?? 'USD')
            ->setPaymentMethod('CryptoCloud')
            ->setStatus('paid')
            ->setPlan($plan);

        $this->em->persist($transaction);
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