<?php

declare(strict_types=1);

namespace App\Enum;

enum IntervalEnum: string
{
    case NOT_SET = 'not_set';
    case TEST_1_MIN = 'TEST_1_min';
    case HOUR_1 = '1_hour';
    case HOURS_5 = '5_hours';
    case HOURS_12 = '12_hours';
    case DAY_1 = '1_day';
    case DAY_3 = '3_days';
    case DAY_7 = '7_days';
    case MONTH = '1_month';


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
