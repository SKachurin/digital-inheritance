<?php

declare(strict_types=1);

namespace App\Message;

final class PaymentExpirationReminderMessage
{
    public function __construct(
        private readonly int $customerId,
        private readonly int $daysLeft
    ) {
    }

    public function getCustomerId(): int
    {
        return $this->customerId;
    }

    public function getDaysLeft(): int
    {
        return $this->daysLeft;
    }
}