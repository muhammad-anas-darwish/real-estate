<?php

namespace Modules\ServiceProvider\Enums;

enum ServiceType: string
{
    case PHOTOGRAPHY = 'photography';
    case INSPECTION = 'inspection';
    case LEGAL = 'legal';
    case MARKETING = 'marketing';

    public function label(): string
    {
        return match ($this) {
            self::PHOTOGRAPHY => 'Photography',
            self::INSPECTION => 'Inspection',
            self::LEGAL => 'Legal Consultation',
            self::MARKETING => 'Marketing',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
