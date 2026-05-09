<?php

namespace Modules\Communication\Enums;

enum DeviceTypeEnum: string
{
    case ANDROID = 'android';
    case IOS = 'ios';
    case WEB = 'web';

    public function label(): string
    {
        return match ($this) {
            self::ANDROID => 'أندرويد',
            self::IOS => 'آيفون',
            self::WEB => 'ويب',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
