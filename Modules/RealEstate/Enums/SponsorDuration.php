<?php

namespace Modules\RealEstate\Enums;

enum SponsorDuration: string
{
    case DAYS_7 = '7_days';
    case DAYS_14 = '14_days';
    case DAYS_30 = '30_days';

    public function label(): string
    {
        return match ($this) {
            self::DAYS_7 => '7 Days',
            self::DAYS_14 => '14 Days',
            self::DAYS_30 => '30 Days',
        };
    }

    public function days(): int
    {
        return match ($this) {
            self::DAYS_7 => 7,
            self::DAYS_14 => 14,
            self::DAYS_30 => 30,
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
