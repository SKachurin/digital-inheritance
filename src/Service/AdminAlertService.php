<?php

declare(strict_types=1);

namespace App\Service;

use App\Controller\PythonServiceController;
use Psr\Log\LoggerInterface;

class AdminAlertService
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly PythonServiceController $pythonServiceController,
        private readonly string $admin_tg,
    ) {
    }

    public function notify(string $message): void
    {
        if ($this->admin_tg === '') {
            $this->logger->error('Admin alert skipped: admin_tg is empty.', [
                'message' => $message,
            ]);

            return;
        }

        try {
            $this->pythonServiceController->callPythonService([$this->admin_tg], $message);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to send admin alert.', [
                'exception' => $e->getMessage(),
                'message' => $message,
            ]);
        }
    }

    public function notifyMissingPaidUntil(int $customerId, int $transactionId): void
    {
        $message = sprintf(
            'ALERT: Customer ID %d has PAID status, but latest paid transaction ID %d has no paidUntil.',
            $customerId,
            $transactionId
        );

        $this->notify($message);
    }
}