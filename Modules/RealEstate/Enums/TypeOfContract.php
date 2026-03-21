<?php

namespace Modules\RealEstate\Enums;

enum TypeOfContract: string
{
    case Sale = 'sale';
    case Rent = 'rent';

    public function label(): string
    {
        return match($this) {
            self::Sale => 'Sale',
            self::Rent => 'Rent',
        };
    }
}
