<?php

namespace Modules\Auth\Enums;

enum ContactPreference: string
{
    case CHAT = 'chat';
    case EXTERNAL = 'external';

    public function label(): string
    {
        return match ($this) {
            self::CHAT => 'Internal Chat',
            self::EXTERNAL => 'External Contact',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
