<?php

namespace Modules\Communication\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Auth\Entities\User;
use Modules\Communication\Entities\ChatRoom;

class ChatRoomPolicy
{
    use HandlesAuthorization;

    public function view(User $user, ChatRoom $room): bool
    {
        return $room->participants()->where('user_id', $user->id)->exists();
    }

    public function send(User $user, ChatRoom $room): bool
    {
        return $room->participants()->where('user_id', $user->id)->exists();
    }

    public function update(User $user, ChatRoom $room): bool
    {
        return false;
    }

    public function delete(User $user, ChatRoom $room): bool
    {
        return false;
    }
}
