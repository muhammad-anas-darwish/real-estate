<?php

namespace Modules\Communication\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Auth\Entities\User;
use Modules\Communication\Entities\Conversation;

class ConversationPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Conversation $conversation): bool
    {
        return $user->id === $conversation->initiator_id
            || $user->id === $conversation->recipient_id;
    }

    public function update(User $user, Conversation $conversation): bool
    {
        return $user->id === $conversation->initiator_id
            || $user->id === $conversation->recipient_id;
    }

    public function delete(User $user, Conversation $conversation): bool
    {
        return $user->id === $conversation->initiator_id
            || $user->id === $conversation->recipient_id;
    }
}
