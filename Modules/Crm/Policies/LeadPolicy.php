<?php

namespace Modules\Crm\Policies;

use Modules\Auth\Entities\User;
use Modules\Crm\Entities\Lead;

class LeadPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('leads.list');
    }

    public function view(User $user, Lead $lead): bool
    {
        return $lead->isOwnedBy($user->id);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('leads.create');
    }

    public function update(User $user, Lead $lead): bool
    {
        return $lead->isOwnedBy($user->id);
    }

    public function delete(User $user, Lead $lead): bool
    {
        return $lead->isOwnedBy($user->id)
            && $lead->notes()->count() === 0
            && $lead->followUps()->count() === 0;
    }

    public function changeStatus(User $user, Lead $lead): bool
    {
        return $lead->isOwnedBy($user->id) && $user->hasPermissionTo('leads.change-status');
    }

    public function archive(User $user, Lead $lead): bool
    {
        return $lead->isOwnedBy($user->id) && $user->hasPermissionTo('leads.archive');
    }

    public function restore(User $user, Lead $lead): bool
    {
        return $lead->isOwnedBy($user->id) && $user->hasPermissionTo('leads.restore');
    }
}
