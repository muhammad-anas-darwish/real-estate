<?php

namespace Modules\RealEstate\Enums;

enum AppointmentType: string
{
    case VIEWING = 'viewing';
    case FOLLOW_UP = 'follow_up';
    case GENERAL = 'general';

    public function label(): string
    {
        return match ($this) {
            self::VIEWING => 'Viewing',
            self::FOLLOW_UP => 'Follow Up',
            self::GENERAL => 'General',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
