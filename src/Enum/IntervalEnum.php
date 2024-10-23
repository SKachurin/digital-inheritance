<?php

declare(strict_types=1);

namespace App\Enum;

enum IntervalEnum: string
{
    case NOT_SET = 'not set';
    case TEST_1_MIN = 'TEST 1 min';
    case HOUR_1 = '1 hour';
    case HOURS_5 = '5 hours';
    case HOURS_12 = '12 hours';
    case DAY_1 = '1 day';
    case DAY_3 = '3 days';
    case DAY_7 = '7 days';
    case MONTH = '1 month';


    /**
     * @return array<int, string>
     */
    public static function getValues(): array
    {
        return [
            self::NOT_SET,
            self::TEST_1_MIN->value,
            self::HOUR_1->value,
            self::HOURS_5->value,
            self::HOURS_12->value,
            self::DAY_1->value,
            self::DAY_3->value,
            self::DAY_7->value,
            self::MONTH->value,
        ];
    }
}
