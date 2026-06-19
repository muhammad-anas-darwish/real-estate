<?php

namespace Modules\Ledger\Enums;

enum AccountCategory: string
{
    case ASSET = 'asset';
    case LIABILITY = 'liability';
    case EQUITY = 'equity';
    case REVENUE = 'revenue';
    case EXPENSE = 'expense';

    public function label(): string
    {
        return match ($this) {
            self::ASSET => 'Assets',
            self::LIABILITY => 'Liabilities',
            self::EQUITY => 'Equity',
            self::REVENUE => 'Revenue',
            self::EXPENSE => 'Expenses',
        };
    }

    public function labelAr(): string
    {
        return match ($this) {
            self::ASSET => 'الأصول',
            self::LIABILITY => 'الخصوم',
            self::EQUITY => 'حقوق الملكية',
            self::REVENUE => 'الإيرادات',
            self::EXPENSE => 'المصروفات',
        };
    }

    public function normalBalance(): string
    {
        return match ($this) {
            self::ASSET, self::EXPENSE => 'debit',
            self::LIABILITY, self::EQUITY, self::REVENUE => 'credit',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
