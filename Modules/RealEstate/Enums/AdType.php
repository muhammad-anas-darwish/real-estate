<?php

namespace Modules\RealEstate\Enums;

enum AdType: string
{
    case BANNER = 'banner';
    case SPONSORED = 'sponsored';

    public function label(): string
    {
        return match ($this) {
            self::BANNER => 'Banner Ad (Admin)',
            self::SPONSORED => 'Sponsored Ad (Paid)',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
