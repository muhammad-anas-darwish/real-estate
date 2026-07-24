<?php

namespace Modules\Deposit\Enums;

enum DepositStatus: string
{
    case PENDING = 'pending';
    case HELD = 'held';
    case RELEASED = 'released';
    case REFUNDED = 'refunded';
    case DISPUTED = 'disputed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::HELD => 'Held',
            self::RELEASED => 'Released',
            self::REFUNDED => 'Refunded',
            self::DISPUTED => 'Disputed',
            self::CANCELLED => 'Cancelled',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
