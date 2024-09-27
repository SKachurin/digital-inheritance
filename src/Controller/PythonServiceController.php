<?php
namespace App\Controller;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class PythonServiceController extends AbstractController
{
    public function callPythonService(?array $users, ?string $message = null): JsonResponse
    {
        $url = 'http://python-service:5000/process_telegram_data';

        if(!$users) {
            $users = ['@preved', '+79109019184'];
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