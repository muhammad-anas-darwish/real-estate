<?php

namespace Modules\RealEstate\Services;

use Illuminate\Support\Facades\Auth;
use Modules\RealEstate\Entities\Property;
use Modules\RealEstate\Enums\PropertyStatus;

class PropertyStatusService
{
    public function handle(Property $property, PropertyStatus $status): void
    {
        match ($status) {
            PropertyStatus::APPROVED => $this->handleApproved($property),
            PropertyStatus::REJECTED => $this->handleRejected($property),
            PropertyStatus::SUSPENDED => $this->handleSuspended($property),
            PropertyStatus::SOLD => $this->handleSold($property),
            PropertyStatus::ARCHIVED => $this->handleArchived($property),
            PropertyStatus::PENDING => $this->handlePending($property),
        };
    }

    protected function handleApproved(Property $property): void
    {
        $property->update([
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'status' => PropertyStatus::APPROVED,
        ]);
    }

    protected function handleRejected(Property $property): void
    {
        $property->update(['status' => PropertyStatus::REJECTED]);
    }

    protected function handleSuspended(Property $property): void
    {
        $property->update(['status' => PropertyStatus::SUSPENDED]);
    }

    protected function handleSold(Property $property): void
    {
        $property->update(['status' => PropertyStatus::SOLD]);
    }

    protected function handleArchived(Property $property): void
    {
        $property->update(['status' => PropertyStatus::ARCHIVED]);
    }

    protected function handlePending(Property $property): void
    {
        $property->update(['status' => PropertyStatus::PENDING]);
    }
}
