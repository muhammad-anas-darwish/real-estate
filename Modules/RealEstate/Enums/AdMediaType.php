<?php

namespace Modules\RealEstate\Enums;

enum AdMediaType: string
{
    case VIDEO = 'video';
    case IMAGE = 'image';

    public function label(): string
    {
        return match ($this) {
            self::VIDEO => 'Video',
            self::IMAGE => 'Image',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
