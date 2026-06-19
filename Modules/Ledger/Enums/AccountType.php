<?php

namespace Modules\Ledger\Enums;

enum AccountType: string
{
    case USER_BALANCE = 'user_balance';
    case REVENUE = 'revenue';
    case CLEARING = 'clearing';
    case PLATFORM_FEE = 'platform_fee';
    case LIABILITY = 'liability';
    case EXPENSE = 'expense';

    public function label(): string
    {
        return match ($this) {
            self::USER_BALANCE => 'User Balance',
            self::REVENUE => 'Revenue',
            self::CLEARING => 'Clearing',
            self::PLATFORM_FEE => 'Platform Fee',
            self::LIABILITY => 'Liability',
            self::EXPENSE => 'Expense',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
