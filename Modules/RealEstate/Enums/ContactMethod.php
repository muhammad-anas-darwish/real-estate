<?php

namespace Modules\RealEstate\Enums;

enum ContactMethod: string
{
    case CALL = 'call';
    case WHATSAPP = 'whatsapp';
    case VISIT = 'visit';
    case EMAIL = 'email';

    public function label(): string
    {
        return match ($this) {
            self::CALL => 'Call',
            self::WHATSAPP => 'WhatsApp',
            self::VISIT => 'Visit',
            self::EMAIL => 'Email',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
