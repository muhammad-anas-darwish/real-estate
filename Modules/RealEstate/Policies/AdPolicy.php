<?php

namespace Modules\RealEstate\Policies;

use Modules\Auth\Entities\User;
use Modules\RealEstate\Entities\Ad;

class AdPolicy
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
        return $user->hasPermissionTo('ads.list');
    }

    public function view(User $user, Ad $ad): bool
    {
        return $user->hasPermissionTo('ads.show')
            && $ad->created_by === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('ads.create');
    }

    public function update(User $user, Ad $ad): bool
    {
        return $user->hasPermissionTo('ads.edit')
            && $ad->created_by === $user->id;
    }

    public function delete(User $user, Ad $ad): bool
    {
        return $user->hasPermissionTo('ads.edit')
            && $ad->created_by === $user->id;
    }

    public function restore(User $user, Ad $ad): bool
    {
        return false;
    }

    public function setStatus(User $user, Ad $ad): bool
    {
        return $user->hasPermissionTo('ads.edit')
            && $ad->created_by === $user->id;
    }

    public function linkProperty(User $user, Ad $ad): bool
    {
        return $user->hasPermissionTo('ads.edit')
            && $ad->created_by === $user->id;
    }

    public function unlinkProperty(User $user, Ad $ad): bool
    {
        return $user->hasPermissionTo('ads.edit')
            && $ad->created_by === $user->id;
    }
}
