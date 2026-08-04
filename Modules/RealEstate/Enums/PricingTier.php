<?php

namespace Modules\RealEstate\Enums;

enum PricingTier: string
{
    case BASIC = 'basic';
    case STANDARD = 'standard';
    case PREMIUM = 'premium';

    public function label(): string
    {
        return match ($this) {
            self::BASIC => 'Basic',
            self::STANDARD => 'Standard',
            self::PREMIUM => 'Premium',
        };
    }

    /**
     * Default pricing for each tier (per duration period).
     */
    public function defaultPrices(): array
    {
        return match ($this) {
            self::BASIC => ['7_days' => 4.99, '14_days' => 8.99, '30_days' => 14.99],
            self::STANDARD => ['7_days' => 9.99, '14_days' => 17.99, '30_days' => 29.99],
            self::PREMIUM => ['7_days' => 19.99, '14_days' => 34.99, '30_days' => 59.99],
        };
    }

    /**
     * Priority/weight for ad rotation (higher = shown more often).
     */
    public function rotationWeight(): int
    {
        return match ($this) {
            self::BASIC => 1,
            self::STANDARD => 2,
            self::PREMIUM => 4,
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
