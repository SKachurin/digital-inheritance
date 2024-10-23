<?php

declare(strict_types=1);

namespace App\Enum;

enum PipelineStatusEnum: string implements TransformableEnumInterface
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

    public static function fromString(string $status): ?self
    {
        return match ($status) {
            self::ACTIVATED->value => self::ACTIVATED,
            self::PENDING->value => self::PENDING,
            self::SUCCESS->value => self::SUCCESS,
            self::FAIL->value => self::FAIL,
            default => null,
        };
    }
}
