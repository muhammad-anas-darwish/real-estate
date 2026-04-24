<?php

namespace Modules\Communication\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Auth\Entities\User;
use Modules\Communication\Entities\Message;

class MessagePolicy
{
    use HandlesAuthorization;

    public function view(User $user, Message $message): bool
    {
        return $message->room->participants()->where('user_id', $user->id)->exists();
    }

    public function delete(User $user, Message $message): bool
    {
        return $message->sender_id === $user->id;
    }
}