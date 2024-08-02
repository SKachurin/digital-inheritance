<?php

declare(strict_types=1);

namespace App\Enum;

enum ContactTypeEnum //: string implements TransformableEnumInterface
{
//    case EMAIL = 'email';
//    case PHONE = 'phone';
//    case MESSENGER = 'messenger';
//
//    /**
//     * @return array<int, string>
//     */
//    public static function getValues(): array
//    {
//        return [
//            self::EMAIL->value,
//            self::PHONE->value,
//            self::MESSENGER->value,
//
//        ];
//    }

    public const EMAIL = 'email';
    public const PHONE = 'phone';
    public const MESSENGER = 'messenger';
    public const SOCIAL = 'social';

    /**
     * @return array<int, string>
     */
    public static function getValues(): array
    {
        return [
            self::EMAIL,
            self::PHONE,
            self::MESSENGER,
            self::SOCIAL,
        ];
    }
}
