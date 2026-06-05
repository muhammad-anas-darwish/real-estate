<?php

namespace Modules\RealEstate\Expert\Enums;

enum ExpertRequestStatus: string
{
    case Pending = 'pending';
    case Resolved = 'resolved';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Resolved => 'Resolved',
        };
    }
}
