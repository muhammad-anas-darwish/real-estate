<?php

namespace Modules\RealEstate\Expert\Enums;

enum ExpertRelationshipStatus: string
{
    case Active = 'active';
    case Cancelled = 'cancelled';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Cancelled => 'Cancelled',
            self::Completed => 'Completed',
        };
    }
}
