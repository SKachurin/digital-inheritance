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

//    /**
//     * @return array<int, string>
//     */
//    public static function getValues(): array
//    {
//        return [
//            self::NOT_SET,
//            self::TEST_1_MIN->value,
//            self::HOUR_1->value,
//            self::HOURS_5->value,
//            self::HOURS_12->value,
//            self::DAY_1->value,
//            self::DAY_3->value,
//            self::DAY_7->value,
//            self::MONTH->value,
//        ];
//    }

    /**
     * @return array<string>
     */
    public static function getValues(): array
    {
        return array_map(fn(self $case) => $case->value, self::cases());
    }

    public static function fromString(string $status): ?self //self::tryFrom ?
    {
        return match ($status) {
            self::NOT_SET->value => self::NOT_SET,
            self::TEST_1_MIN->value => self::TEST_1_MIN,
            self::HOUR_1->value => self::HOUR_1,
            self::HOURS_5->value => self::HOURS_5,
            self::HOURS_12->value => self::HOURS_12,
            self::DAY_1->value => self::DAY_1,
            self::DAY_3->value => self::DAY_3,
            self::DAY_7->value => self::DAY_7,
            self::MONTH->value => self::MONTH,
            default => null,
        };
    }

    public function toDateInterval(): \DateInterval
    {
        return match ($this) {
            self::NOT_SET => new \DateInterval('PT0S'),
            self::TEST_1_MIN => new \DateInterval('PT1M'),
            self::HOUR_1 => new \DateInterval('PT1H'),
            self::HOURS_5 => new \DateInterval('PT5H'),
            self::HOURS_12 => new \DateInterval('PT12H'),
            self::DAY_1 => new \DateInterval('P1D'),
            self::DAY_3 => new \DateInterval('P3D'),
            self::DAY_7 => new \DateInterval('P7D'),
            self::MONTH => new \DateInterval('P1M'),
        };
    }
}
