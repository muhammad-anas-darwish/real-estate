<?php

namespace Modules\Communication\Enums;

enum MessageTypeEnum: string
{
    case TEXT = 'text';
    case IMAGE = 'image';
    case FILE = 'file';

    public function label(): string
    {
        return match ($this) {
            self::TEXT => 'نص',
            self::IMAGE => 'صورة',
            self::FILE => 'ملف',
        };
    }

    public function allowedMimeTypes(): array
    {
        return match ($self) {
            self::TEXT => ['text/plain'],
            self::IMAGE => ['image/jpeg', 'image/png', 'image/webp'],
            self::FILE => [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ],
        };
    }

    public static function allowedMimeTypesForMessage(): array
    {
        return array_merge(
            self::IMAGE->allowedMimeTypes(),
            self::FILE->allowedMimeTypes()
        );
    }
}
