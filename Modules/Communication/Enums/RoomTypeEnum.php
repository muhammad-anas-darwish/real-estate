<?php

namespace Modules\Communication\Enums;

enum RoomTypeEnum: string
{
    case PRIVATE = 'private';
    case GROUP = 'group';

    public function label(): string
    {
        return match ($this) {
            self::PRIVATE => 'خاص',
            self::GROUP => 'مجموعة',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}