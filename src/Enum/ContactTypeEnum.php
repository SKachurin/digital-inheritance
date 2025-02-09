<?php

declare(strict_types=1);

namespace App\Enum;

enum  ContactTypeEnum: string
{
    case EMAIL = 'email';
    case PHONE = 'phone';
    case MESSENGER = 'messenger';
    case SOCIAL = 'social';

    /**
     * @return array<string>
     */
    public static function getValues(): array
    {
        return array_map(fn(self $case) => $case->value, self::cases());
    }
}
