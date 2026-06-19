<?php

namespace Modules\Ledger\Enums;

enum PayrollPaymentStatus: string
{
    case PENDING = 'pending';
    case PAID = 'paid';
    case FAILED = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::PAID => 'Paid',
            self::FAILED => 'Failed',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
