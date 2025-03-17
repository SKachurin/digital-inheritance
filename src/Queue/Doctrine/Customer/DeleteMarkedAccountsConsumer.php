<?php

namespace App\Queue\Doctrine\Customer;

use App\Message\DeleteMarkedAccountsMessage;
use App\Service\CustomerCleanupService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class DeleteMarkedAccountsConsumer
{
    public function __construct(private readonly CustomerCleanupService $cleanupService) {}

    public function __invoke(DeleteMarkedAccountsMessage $message): void
    {
        $this->cleanupService->deleteMarkedAccounts();
    }
}
