<?php

namespace App\Controller;

use App\Service\CronService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Psr\Log\LoggerInterface;

class CronController extends AbstractController
{
    private LoggerInterface $logger;

    public function fiveMinutesCheck(Request $request, CronService $cronService): Response
    {
        // Validate the token
        $secretToken = $this->getParameter('cron_secret_token');
        $providedToken = str_replace('Bearer ', '', $request->headers->get('Authorization', ''));

        if ($secretToken !== $providedToken) {
            return new Response('Unauthorized', Response::HTTP_UNAUTHORIZED);
        }

        try {
            $cronService->executeFiveMinuteTasks();

            return new Response('Tasks executed successfully.', Response::HTTP_OK);

        } catch (\Exception $e) {

            $this->logger->error('Error executing cron tasks: ' . $e->getMessage());

            return new Response('Internal Server Error', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
