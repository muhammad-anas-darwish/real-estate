<?php

namespace Modules\RealEstate\Enums;

enum ViewingType: string
{
    case IN_PERSON = 'in_person';
    case VIRTUAL = 'virtual';
    case OPEN_HOUSE = 'open_house';

    public function label(): string
    {
        return match ($this) {
            self::IN_PERSON => 'In Person',
            self::VIRTUAL => 'Virtual',
            self::OPEN_HOUSE => 'Open House',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
