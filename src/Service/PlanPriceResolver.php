<?php

declare(strict_types=1);

namespace App\Service;

class PlanPriceResolver
{
    public const STANDARD = 'standard';
    public const PREMIUM = 'premium';

    public function getPricePerMonth(string $plan): ?int
    {
        return match ($plan) {
            self::STANDARD => 5,
            self::PREMIUM => 1, //TODO 25
            default => null,
        };
    }

    public function getAllPrices(): array
    {
        return [
            self::STANDARD => $this->getPricePerMonth(self::STANDARD),
            self::PREMIUM => $this->getPricePerMonth(self::PREMIUM),
        ];
    }
}
