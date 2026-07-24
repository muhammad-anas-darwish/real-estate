<?php

namespace Modules\FileSystem\Enums;

enum FileType: string
{
    case TEXT = 'text';
    case IMAGE = 'image';

    public function allowedExtensions(): array
    {
        return match ($this) {
            self::TEXT => [],
            self::IMAGE => ['jpg', 'jpeg', 'png', 'webp', 'gif'],
        };
    }

    public function maxSizeBytes(): int
    {
        return match ($this) {
            self::TEXT => 5_242_880,
            self::IMAGE => 10_485_760,
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
