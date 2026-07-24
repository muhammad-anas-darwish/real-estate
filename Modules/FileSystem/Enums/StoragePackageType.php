<?php

namespace Modules\FileSystem\Enums;

enum StoragePackageType: string
{
    case FREE = 'free';
    case SMALL = 'small';
    case MEDIUM = 'medium';
    case LARGE = 'large';
    case MAX = 'max';

    public function quotaBytes(): int
    {
        return match ($this) {
            self::FREE => 104_857_600,
            self::SMALL => 524_288_000,
            self::MEDIUM => 1_073_741_824,
            self::LARGE => 3_221_225_472,
            self::MAX => 5_368_709_120,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::FREE => 'Free',
            self::SMALL => 'Small',
            self::MEDIUM => 'Medium',
            self::LARGE => 'Large',
            self::MAX => 'Max',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
