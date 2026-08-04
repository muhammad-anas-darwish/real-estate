<?php

namespace Modules\ServiceProvider\Enums;

enum PricingType: string
{
    case FIXED = 'fixed';
    case HOURLY = 'hourly';
    case NEGOTIABLE = 'negotiable';

    public function label(): string
    {
        return match ($this) {
            self::FIXED => 'Fixed',
            self::HOURLY => 'Hourly',
            self::NEGOTIABLE => 'Negotiable',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
