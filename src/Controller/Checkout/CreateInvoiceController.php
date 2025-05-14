<?php

declare(strict_types=1);

namespace App\Controller\Checkout;

use App\Application\Dto\CreateInvoiceRequestDto;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class CreateInvoiceController extends AbstractController
{
    public function __construct(
        private HttpClientInterface $http,
        private string              $apiKey
    )
    {
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function __invoke(Request $request, UrlGeneratorInterface $urlGenerator): JsonResponse
    {
        try {
            $dto = $this->mapRequestToDto($request);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => 'Invalid input: ' . $e->getMessage()], 400);
        }

        $user = $this->getUser();

        if (!$user instanceof \App\Entity\Customer) {
            return new JsonResponse(['error' => 'Unauthenticated'], 401);
        }

//        $orderId = sprintf('order_%d/%e/%s', $user->getId(), $dto->plan, uniqid());
        $orderId =  sprintf('order_%d/%s/%s', $user->getId(), $dto->plan, uniqid());

        $response = $this->http->request('POST', 'https://api.cryptocloud.plus/v2/invoice/create', [
            'headers' => [
                'Authorization' => 'Token ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Accept-Encoding' => 'identity',
                'User-Agent' => 'TheDigitalHeirBot/1.0',
            ],
            'json' => [
                'shop_id' => '4Hj4s1isHiUkAAz2',
                'amount' => $dto->amount,
                'currency' => 'USD',
                'order_id' => $orderId,
                'email' => $user->getCustomerEmail() ?? 'no-reply@thedigitalheir.com',
                'description' => ucfirst($dto->plan) . " Plan x{$dto->quantity} months",
                'lifetime' => 600,
                'success_url' => $urlGenerator->generate('user_home', [], UrlGeneratorInterface::ABSOLUTE_URL),
                'fail_url' => $urlGenerator->generate('checkout', [], UrlGeneratorInterface::ABSOLUTE_URL),
            ]
        ]);

        try {
            $invoice = $response->toArray(false);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'error' => 'API failed',
                'details' => $e->getMessage(),
                'raw_response' => $response->getContent(false),
            ], 500);
        }

        if (($invoice['status'] ?? '') === 'success' && isset($invoice['result']['link'])) {
            return new JsonResponse(['invoice_url' => $invoice['result']['link']]);
        }

        return new JsonResponse(['error' => 'Failed to create invoice'], 500);
    }

    /**
     * @throws \JsonException
     */
    private function mapRequestToDto(Request $request): CreateInvoiceRequestDto
    {
        $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

        if (!isset($data['plan'], $data['amount'])) {
            throw new \InvalidArgumentException('Missing required parameters');
        }

        return new CreateInvoiceRequestDto(
            plan: $data['plan'],
            amount: (float) $data['amount'],
            quantity: (int) ($data['quantity'] ?? 1)
        );
    }
}
