<?php

namespace Modules\RealEstate\Policies;

use Modules\Auth\Entities\User;
use Modules\RealEstate\Entities\PropertyViewing;
use Modules\RealEstate\Enums\ViewingStatus;

class PropertyViewingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['viewings.list']);
    }

    public function view(User $user, PropertyViewing $propertyViewing): bool
    {
        if ($user->hasPermissionTo('viewings.show')) {
            return true;
        }

        return $propertyViewing->user_id === $user->id
            || $propertyViewing->agent_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('viewings.create');
    }

    public function update(User $user, PropertyViewing $propertyViewing): bool
    {
        if ($user->hasPermissionTo('viewings.edit')) {
            return true;
        }

        return $propertyViewing->agent_id === $user->id;
    }

    public function delete(User $user, PropertyViewing $propertyViewing): bool
    {
        if ($user->hasPermissionTo('viewings.delete')) {
            return true;
        }

        return $propertyViewing->user_id === $user->id;
    }

    public function confirm(User $user, PropertyViewing $propertyViewing): bool
    {
        return $this->canChangeStatus($user, $propertyViewing);
    }

    public function cancel(User $user, PropertyViewing $propertyViewing): bool
    {
        return $user->hasPermissionTo('viewings.cancel')
            || $propertyViewing->agent_id === $user->id
            || $propertyViewing->user_id === $user->id;
    }

    public function reschedule(User $user, PropertyViewing $propertyViewing): bool
    {
        return $this->canChangeStatus($user, $propertyViewing);
    }

    public function complete(User $user, PropertyViewing $propertyViewing): bool
    {
        return $this->canChangeStatus($user, $propertyViewing);
    }

    public function markNoShow(User $user, PropertyViewing $propertyViewing): bool
    {
        return $this->canChangeStatus($user, $propertyViewing);
    }

    private function canChangeStatus(User $user, PropertyViewing $propertyViewing): bool
    {
        if ($user->hasPermissionTo('viewings.edit')) {
            return true;
        }

        return $propertyViewing->agent_id === $user->id;
    }
}
