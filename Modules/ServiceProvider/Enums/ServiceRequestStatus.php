<?php

namespace Modules\ServiceProvider\Enums;

enum ServiceRequestStatus: string
{
    case PENDING = 'pending';
    case ACCEPTED = 'accepted';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case REJECTED = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::ACCEPTED => 'Accepted',
            self::IN_PROGRESS => 'In Progress',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
            self::REJECTED => 'Rejected',
        };
    }

    public function canTransitionTo(self $newStatus): bool
    {
        return match ($this) {
            self::PENDING => in_array($newStatus, [self::ACCEPTED, self::REJECTED, self::CANCELLED]),
            self::ACCEPTED => in_array($newStatus, [self::IN_PROGRESS, self::CANCELLED]),
            self::IN_PROGRESS => in_array($newStatus, [self::COMPLETED, self::CANCELLED]),
            self::COMPLETED, self::CANCELLED, self::REJECTED => false,
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
