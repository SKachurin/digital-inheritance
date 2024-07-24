<?php

declare(strict_types=1);

namespace App\Enum;

enum ActionStatusEnum: string implements TransformableEnumInterface
{
    case ACTIVATED = 'activated';
    case PENDING = 'pending';
    case SUCCESS = 'success';
    case FAIL = 'fail';

    /**
     * @return array<int, string>
     */
    public static function getValues(): array
    {
        return [
            self::ACTIVATED->value,
            self::PENDING->value,
            self::SUCCESS->value,
            self::FAIL->value
        ];
    }
}
