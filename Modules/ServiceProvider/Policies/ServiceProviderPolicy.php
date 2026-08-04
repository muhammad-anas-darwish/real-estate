<?php

namespace Modules\ServiceProvider\Policies;

use Modules\Auth\Entities\User;
use Modules\ServiceProvider\Entities\ServiceProviderProfile;

class ServiceProviderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('service_providers.list');
    }

    public function view(User $user, ServiceProviderProfile $profile): bool
    {
        return $user->hasPermissionTo('service_providers.show')
            || $user->id === $profile->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, ServiceProviderProfile $profile): bool
    {
        return $user->id === $profile->user_id
            || $user->hasPermissionTo('service_providers.edit');
    }

    public function verify(User $user): bool
    {
        return $user->hasPermissionTo('service_providers.verify');
    }

    public function unverify(User $user): bool
    {
        return $user->hasPermissionTo('service_providers.unverify');
    }
}
