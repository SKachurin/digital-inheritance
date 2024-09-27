<?php

declare(strict_types=1);

namespace App\Enum;

enum CustomerSocialAppEnum: string implements TransformableEnumInterface
{
//    case FACEBOOK = 'facebook';
//    case INSTAGRAM = 'instagram';
//    case VKCOM = 'vk.com';
    case TELEGRAM = 'telegram';
    case NONE = 'none';


    /**
     * @return array<int, string>
     */
    public static function getValues(): array
    {
        return [
//            self::FACEBOOK->value,
//            self::INSTAGRAM->value,
//            self::VKCOM->value,
            self::TELEGRAM->value,
            self::NONE->value,
        ];
    }
}
