<?php

namespace Modules\Crm\Policies;

use Modules\Auth\Entities\User;
use Modules\Crm\Entities\LeadNote;

class LeadNotePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('lead_notes.list');
    }

    public function view(User $user, LeadNote $note): bool
    {
        return $note->lead->isOwnedBy($user->id);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('lead_notes.create');
    }

    public function update(User $user, LeadNote $note): bool
    {
        return $note->isEditableBy($user);
    }

    public function delete(User $user, LeadNote $note): bool
    {
        return $note->isDeletableBy($user);
    }
}
