<?php

namespace Modules\RealEstate\Policies;

use Modules\Auth\Entities\User;
use Modules\RealEstate\Entities\Property;
use Modules\RealEstate\Enums\PropertyStatus;

class PropertyPolicy
{
    public function updateStatus(User $user, Property $property, PropertyStatus $newStatus): bool
    {
        if ($user->hasPermissionTo('properties.edit')) {
            return in_array($newStatus, [
                PropertyStatus::APPROVED,
                PropertyStatus::REJECTED,
                PropertyStatus::SUSPENDED,
                PropertyStatus::SOLD,
                PropertyStatus::ARCHIVED,
            ]);
        }

        if ($property->publisher_id === $user->id) {
            return $newStatus === PropertyStatus::SOLD;
        }

        return false;
    }
}
