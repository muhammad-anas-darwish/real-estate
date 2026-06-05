<?php

namespace Modules\RealEstate\Expert\Enums;

enum ExpertType: string
{
    case Photographer = 'photographer';
    case Lawyer = 'lawyer';
    case Consultant = 'consultant';

    public function label(): string
    {
        return match ($this) {
            self::Photographer => 'Photographer',
            self::Lawyer => 'Lawyer',
            self::Consultant => 'Consultant',
        };
    }
}
