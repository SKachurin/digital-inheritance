<?php

declare(strict_types=1);

namespace App\Enum;

enum IntervalEnum: string
{
    case TEST_1_MIN = 'TEST 1 min';
    case HOUR_1 = '1 hour';
    case HOURS_5 = '5 hours';
    case HOURS_12 = '12 hours';
    case DAY_1 = '1 day';
    case MONTH = '1 month';


    /**
     * @return array<int, string>
     */
    public static function getValues(): array
    {
        return [
            self::TEST_1_MIN->value,
            self::HOUR_1->value,
            self::HOURS_5->value,
            self::HOURS_12->value,
            self::DAY_1->value,
            self::MONTH->value,
        ];
    }
}
