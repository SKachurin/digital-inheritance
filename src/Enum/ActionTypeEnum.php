<?php

declare(strict_types=1);

namespace App\Enum;

enum ActionTypeEnum: string
{
    case SOCIAL_CHECK = 'social_check';
    case SOCIAL_SEND = 'social_send';
    case MESSENGER_SEND = 'messenger_send';
    case MESSENGER_SEND_2 = 'messenger_send_2';
    case EMAIL_SEND = 'email_send';
    case EMAIL_SEND_2 = 'email_send_2';
//    case SMS_SEND = 'sms_send';
//    case SMS_SEND_2 = 'sms_send_2';
//    case CALL_MADE = 'call_made';
//    case CALL_MADE_2 = 'call_made_2';

    /**
     * @return array<string>
     */
    public static function getValues(): array
    {
        return array_map(fn(self $case) => $case->value, self::cases());
    }

    //
//    /**
//     * @return array<int, string>
//     */
//    public static function getValues(): array
//    {
//        return [
//            self::SOCIAL_CHECK->value,
//            self::MESSENGER_SEND->value,
//            self::MESSENGER_SEND_2->value,
//            self::EMAIL_SEND->value,
//            self::EMAIL_SEND_2->value,
////            self::SMS_SEND->value,
////            self::SMS_SEND_2->value,
////            self::CALL_MADE->value,
////            self::CALL_MADE_2->value,
//        ];
//    }
}