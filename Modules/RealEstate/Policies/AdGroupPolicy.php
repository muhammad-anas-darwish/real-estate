<?php

namespace Modules\RealEstate\Policies;

use Modules\Auth\Entities\User;
use Modules\RealEstate\Entities\AdGroup;

class AdGroupPolicy
{
    public function before(User $user): ?bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('ad_groups.list');
    }

    public function view(User $user, AdGroup $adGroup): bool
    {
        return $user->hasPermissionTo('ad_groups.show')
            && $adGroup->created_by === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('ad_groups.create');
    }

    public function update(User $user, AdGroup $adGroup): bool
    {
        return $user->hasPermissionTo('ad_groups.edit')
            && $adGroup->created_by === $user->id;
    }

    public function delete(User $user, AdGroup $adGroup): bool
    {
        return false;
    }

    public function restore(User $user, AdGroup $adGroup): bool
    {
        return false;
    }

    public function setDefault(User $user, AdGroup $adGroup): bool
    {
        return $adGroup->created_by === $user->id;
    }
}
