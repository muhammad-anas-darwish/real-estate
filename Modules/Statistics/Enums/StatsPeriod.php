<?php

namespace Modules\Statistics\Enums;

enum StatsPeriod: string
{
    case TODAY = 'today';
    case YESTERDAY = 'yesterday';
    case LAST_7_DAYS = 'last_7_days';
    case LAST_30_DAYS = 'last_30_days';
    case THIS_WEEK = 'this_week';
    case LAST_WEEK = 'last_week';
    case THIS_MONTH = 'this_month';
    case LAST_MONTH = 'last_month';
    case THIS_YEAR = 'this_year';
    case CUSTOM = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::TODAY => 'اليوم',
            self::YESTERDAY => 'أمس',
            self::LAST_7_DAYS => 'آخر 7 أيام',
            self::LAST_30_DAYS => 'آخر 30 يوم',
            self::THIS_WEEK => 'هذا الأسبوع',
            self::LAST_WEEK => 'الأسبوع الماضي',
            self::THIS_MONTH => 'هذا الشهر',
            self::LAST_MONTH => 'الشهر الماضي',
            self::THIS_YEAR => 'هذا العام',
            self::CUSTOM => 'فترة مخصصة',
        };
    }

    public function days(): int
    {
        return match ($this) {
            self::TODAY, self::YESTERDAY => 1,
            self::LAST_7_DAYS, self::THIS_WEEK, self::LAST_WEEK => 7,
            self::LAST_30_DAYS, self::THIS_MONTH, self::LAST_MONTH => 30,
            self::THIS_YEAR => 365,
            self::CUSTOM => 0,
        };
    }

    public function supportsComparison(): bool
    {
        return ! in_array($this, [self::TODAY, self::YESTERDAY, self::CUSTOM], true);
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
