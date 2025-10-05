<?php

declare(strict_types=1);

namespace App\Message;

class ReferralBonusMessage
{
    public function __construct(
        public readonly int $inviterId,
        public readonly float $amount,
        public readonly string $currency,
        public readonly string $invoiceUuid,
    ) {}
}
