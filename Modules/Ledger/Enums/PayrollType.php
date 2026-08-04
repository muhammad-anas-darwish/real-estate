<?php

namespace Modules\Ledger\Enums;

enum PayrollType: string
{
    case MONTHLY = 'monthly';
    case PER_TASK = 'per_task';
    case BOTH = 'both';

    public function label(): string
    {
        return match ($this) {
            self::MONTHLY => 'Monthly Salary',
            self::PER_TASK => 'Per Task',
            self::BOTH => 'Monthly + Per Task',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
