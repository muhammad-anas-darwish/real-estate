<?php

namespace Modules\ServiceProvider\Enums;

enum ServiceTaskType: string
{
    case PHOTO_UPLOAD = 'photo_upload';
    case CHECKLIST = 'checklist';
    case REPORT = 'report';
    case VERIFY = 'verify';

    public function label(): string
    {
        return match ($this) {
            self::PHOTO_UPLOAD => 'Photo Upload',
            self::CHECKLIST => 'Checklist',
            self::REPORT => 'Report',
            self::VERIFY => 'Verify',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
