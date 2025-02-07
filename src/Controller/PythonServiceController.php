<?php
namespace App\Controller;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class PythonServiceController extends AbstractController
{
    private ParameterBagInterface $params;

    public function __construct(ParameterBagInterface $params)
    {
        $this->params = $params;
    }
    public function callPythonService(?array $users, ?string $message = null): JsonResponse
    {
        $url = $this->params->get('telegram_url');

        if(!$users) {
            $users = ['@sergei_rz'];
        }

        $data = [
            'users' => $users,
            'message' => $message
        ];

        $client = HttpClient::create();

        try {
            $response = $client->request('POST', $url, [
                'json' => $data,  //set Content-Type to application/json
            ]);

            $responseData = $response->toArray();

            return new JsonResponse([
                'output' => $responseData
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Failed to call Python service',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}