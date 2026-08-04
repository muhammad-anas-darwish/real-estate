<?php

namespace Modules\Auth\Enums;

enum PublisherType: string
{
    case INDIVIDUAL = 'individual';
    case OFFICE = 'office';

    public function label(): string
    {
        return match ($this) {
            self::INDIVIDUAL => 'Individual',
            self::OFFICE => 'Office',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
