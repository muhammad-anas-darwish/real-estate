<?php

namespace Modules\RealEstate\Enums;

enum PropertyStatus: string
{
    case PENDING = 'pending';
    case UNDER_INSPECTION = 'under_inspection';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case SUSPENDED = 'suspended';
    case SOLD = 'sold';
    case ARCHIVED = 'archived';
    case DRAFT = 'draft';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::UNDER_INSPECTION => 'Under Inspection',
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
            self::SUSPENDED => 'Suspended',
            self::SOLD => 'Sold',
            self::ARCHIVED => 'Archived',
            self::DRAFT => 'Draft',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
