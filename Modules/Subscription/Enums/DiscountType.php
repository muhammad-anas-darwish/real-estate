<?php

namespace Modules\Subscription\Enums;

enum DiscountType: string
{
    case PERCENTAGE = 'percentage';
    case FIXED = 'fixed';

    public function label(): string
    {
        return match ($this) {
            self::PERCENTAGE => 'Percentage',
            self::FIXED => 'Fixed',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
