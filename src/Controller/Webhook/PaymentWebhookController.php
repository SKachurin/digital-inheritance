<?php

declare(strict_types=1);

namespace App\Controller\Webhook;

use App\Service\Payment\CryptoCloudPaymentHandler;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PaymentWebhookController extends AbstractController
{
    public function __construct(
        private readonly CryptoCloudPaymentHandler $paymentHandler,
        private readonly LoggerInterface           $logger,
    )
    {
    }

    public function __invoke(Request $request): Response
    {
        if (!$request->headers->contains('content-type', 'application/x-www-form-urlencoded')) {
            return new Response('Unsupported content type', 415);
        }

        $data = $request->request->all();

        $this->logger->error('CryptoCloud webhook received', [
            'raw_body' => $request->getContent(),
            'decoded_payload' => $data,
            'headers' => $request->headers->all(),
        ]);

        $orderId = $data['order_id'] ?? null;
        $token = $data['token'] ?? null;
        $invoiceId = $data['invoice_id'] ?? null;

        // Basic check
        if (!$token || !$orderId || !$invoiceId ) {
            return new Response('Invalid payload', 400);
        }

        return $this->paymentHandler->handleWebhook($data);
    }
}
