<?php

namespace Modules\RealEstate\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Modules\Communication\Notifications\PropertyStatusChangedNotification;
use Modules\Communication\Services\NotificationService;
use Modules\RealEstate\Entities\Property;
use Modules\RealEstate\Enums\PropertyStatus;

class PropertyStatusService
{
    public function __construct(
        protected readonly NotificationService $notificationService,
    ) {}

    public function handle(Property $property, PropertyStatus $status, ?string $rejectionReason = null): void
    {
        $oldStatus = $property->status->value ?? 'unknown';

        match ($status) {
            PropertyStatus::APPROVED => $this->handleApproved($property, $oldStatus),
            PropertyStatus::REJECTED => $this->handleRejected($property, $oldStatus, $rejectionReason),
            PropertyStatus::SUSPENDED => $this->handleSuspended($property, $oldStatus),
            PropertyStatus::SOLD => $this->handleSold($property, $oldStatus),
            PropertyStatus::ARCHIVED => $this->handleArchived($property, $oldStatus),
            PropertyStatus::PENDING => $this->handlePending($property, $oldStatus),
        };
    }

    protected function handleApproved(Property $property, string $oldStatus): void
    {
        $property->update([
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'status' => PropertyStatus::APPROVED,
            'rejection_reason' => null,
        ]);

        $this->notifyPublisher($property, $oldStatus, PropertyStatus::APPROVED->value);
    }

    protected function handleRejected(Property $property, string $oldStatus, ?string $reason = null): void
    {
        $property->update([
            'status' => PropertyStatus::REJECTED,
            'rejection_reason' => $reason,
        ]);

        $this->notifyPublisher($property, $oldStatus, PropertyStatus::REJECTED->value, $reason);
    }

    protected function handleSuspended(Property $property, string $oldStatus): void
    {
        $property->update(['status' => PropertyStatus::SUSPENDED]);

        $this->notifyPublisher($property, $oldStatus, PropertyStatus::SUSPENDED->value);
    }

    protected function handleSold(Property $property, string $oldStatus): void
    {
        $property->update(['status' => PropertyStatus::SOLD]);

        $this->notifyPublisher($property, $oldStatus, PropertyStatus::SOLD->value);
    }

    protected function handleArchived(Property $property, string $oldStatus): void
    {
        $property->update(['status' => PropertyStatus::ARCHIVED]);
    }

    protected function handlePending(Property $property, string $oldStatus): void
    {
        $property->update(['status' => PropertyStatus::PENDING]);
    }

    protected function notifyPublisher(Property $property, string $oldStatus, string $newStatus, ?string $rejectionReason = null): void
    {
        try {
            $publisher = $property->publisher;

            if (! $publisher) {
                return;
            }

            $notification = new PropertyStatusChangedNotification(
                propertyTitle: $property->name,
                oldStatus: $oldStatus,
                newStatus: $newStatus,
                propertyId: $property->id,
                rejectionReason: $rejectionReason,
            );

            $this->notificationService->notify($publisher, $notification);
        } catch (\Throwable $e) {
            Log::warning('Property status notification failed: '.$e->getMessage());
        }
    }
}
