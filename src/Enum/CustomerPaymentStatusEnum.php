<?php

declare(strict_types=1);

namespace App\Enum;

enum CustomerPaymentStatusEnum: string
{
    case FREE = 'free';
    case PAID = 'paid';


    /**
     * @return array<int, string>
     */
    public static function getValues(): array
    {
        return [
            self::FREE->value,
            self::PAID->value
        ];
    }
}
