<?php

declare(strict_types=1);

namespace App\Enum;

enum CustomerPaymentStatusEnum: string
{
    case NOT_PAID = 'not_paid';
    case PAID = 'paid';


    /**
     * @return array<int, string>
     */
    public static function getValues(): array
    {
        return [
            self::NOT_PAID->value,
            self::PAID->value
        ];
    }

    public static function fromString(string $status): ?self
    {
        return match ($status) {
            self::NOT_PAID->value => self::NOT_PAID,
            self::PAID->value => self::PAID,
            default => null,
        };
    }
}
