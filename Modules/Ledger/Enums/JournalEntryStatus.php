<?php

namespace Modules\Ledger\Enums;

enum JournalEntryStatus: string
{
    case DRAFT = 'draft';
    case POSTED = 'posted';
    case REVERSED = 'reversed';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::POSTED => 'Posted',
            self::REVERSED => 'Reversed',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
