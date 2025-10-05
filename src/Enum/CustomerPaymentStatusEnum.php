<?php

declare(strict_types=1);

namespace App\Enum;

enum CustomerPaymentStatusEnum: string
{
    case NOT_PAID = 'not_paid';
    case PAID = 'paid';
    case TRIAL = 'trial';


    /**
     * @return array<int, string>
     */
    public static function getValues(): array
    {
        return [
            self::NOT_PAID->value,
            self::PAID->value,
            self::TRIAL->value,
        ];
    }

    public static function fromString(string $status): ?self
    {
        return match ($status) {
            self::NOT_PAID->value => self::NOT_PAID,
            self::PAID->value => self::PAID,
            self::TRIAL->value => self::TRIAL,
            default => null,
        };
    }
}
