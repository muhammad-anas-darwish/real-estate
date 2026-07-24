<?php

namespace Modules\RealEstate\Enums;

enum PropertyDirection: string
{
    case NORTH = 'north';
    case SOUTH = 'south';
    case EAST = 'east';
    case WEST = 'west';
    case NORTHEAST = 'northeast';
    case NORTHWEST = 'northwest';
    case SOUTHEAST = 'southeast';
    case SOUTHWEST = 'southwest';

    public function label(): string
    {
        return match ($this) {
            self::NORTH => 'شمالي',
            self::SOUTH => 'جنوبي',
            self::EAST => 'شرقي',
            self::WEST => 'غربي',
            self::NORTHEAST => 'شمالي شرقي',
            self::NORTHWEST => 'شمالي غربي',
            self::SOUTHEAST => 'جنوبي شرقي',
            self::SOUTHWEST => 'جنوبي غربي',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
