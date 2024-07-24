<?php

declare(strict_types=1);

namespace App\Enum;

enum ActionTypeEnum: string
{
    case SOCIAL_CHECK = 'social_check';
    case MESSENGER_SEND = 'messenger_send';
    case SMS_SEND = 'sms_send';
    case SMS_SEND_2 = 'sms_send_2';
    case CALL_MADE = 'call_made';
    case CALL_MADE_2 = 'call_made_2';


    /**
     * @return array<int, string>
     */
    public static function getValues(): array
    {
        return [
            self::SOCIAL_CHECK->value,
            self::MESSENGER_SEND->value,
            self::SMS_SEND->value,
            self::SMS_SEND_2->value,
            self::CALL_MADE->value,
            self::CALL_MADE_2->value,
        ];
    }
}