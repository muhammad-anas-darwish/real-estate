<?php

namespace Modules\RealEstate\Enums;

enum AdStatus: string
{
    case DRAFT = 'draft';
    case ACTIVE = 'active';
    case PAUSED = 'paused';
    case ARCHIVED = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::ACTIVE => 'Active',
            self::PAUSED => 'Paused',
            self::ARCHIVED => 'Archived',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
