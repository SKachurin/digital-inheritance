<?php

namespace App\Enum;
use BackedEnum;

interface TransformableEnumInterface extends BackedEnum
{
    /**
     * @return array<int, string>
     */
    public static function getValues(): array;
}