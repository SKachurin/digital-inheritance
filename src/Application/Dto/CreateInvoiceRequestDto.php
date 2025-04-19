<?php

declare(strict_types=1);

namespace App\Application\Dto;

class CreateInvoiceRequestDto
{
    public function __construct(
        public readonly string $plan,
        public readonly float  $amount,
        public readonly int    $quantity
    )
    {
    }
}