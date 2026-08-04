<?php

namespace Modules\ServiceProvider\Enums;

enum ServiceProviderType: string
{
    case PHOTOGRAPHER = 'photographer';
    case LAWYER = 'lawyer';
    case INSPECTOR = 'inspector';
    case MARKETER = 'marketer';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::PHOTOGRAPHER => 'Photographer',
            self::LAWYER => 'Lawyer',
            self::INSPECTOR => 'Inspector',
            self::MARKETER => 'Marketer',
            self::OTHER => 'Other',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
