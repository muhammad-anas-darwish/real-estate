<?php

namespace Modules\RealEstate\Enums;

enum RentalCardStatus: string
{
    case ACTIVE = 'active';
    case ENDED = 'ended';
    case CANCELLED = 'cancelled';
    case RENEWED = 'renewed';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::ENDED => 'Ended',
            self::CANCELLED => 'Cancelled',
            self::RENEWED => 'Renewed',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::ENDED, self::CANCELLED, self::RENEWED], true);
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
