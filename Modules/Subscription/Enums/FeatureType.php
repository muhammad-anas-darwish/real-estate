<?php

namespace Modules\Subscription\Enums;

enum FeatureType: string
{
    case TOGGLE = 'toggle';
    case LIMIT = 'limit';

    public function label(): string
    {
        return match ($this) {
            self::TOGGLE => 'Toggle',
            self::LIMIT => 'Limit',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
