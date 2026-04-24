<?php

namespace Modules\Communication\Enums;

enum ConversationType: string
{
    case PROPERTY_INQUIRY = 'property_inquiry';
    case GENERAL = 'general';

    public function label(): string
    {
        return match ($this) {
            self::PROPERTY_INQUIRY => 'Property Inquiry',
            self::GENERAL => 'General',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}