<?php

namespace Modules\RealEstate\Enums;

enum ViewingStatus: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case RESCHEDULED = 'rescheduled';
    case CANCELLED = 'cancelled';
    case COMPLETED = 'completed';
    case NO_SHOW = 'no_show';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::CONFIRMED => 'Confirmed',
            self::RESCHEDULED => 'Rescheduled',
            self::CANCELLED => 'Cancelled',
            self::COMPLETED => 'Completed',
            self::NO_SHOW => 'No Show',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
