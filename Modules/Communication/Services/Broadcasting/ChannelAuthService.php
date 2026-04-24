<?php

namespace Modules\Communication\Services\Broadcasting;

use Modules\Auth\Entities\User;
use Modules\Communication\Entities\ChatRoom;

class ChannelAuthService
{
    public function authorizeUserChannel(User $user, int $userId): bool
    {
        return $user->id === $userId;
    }

    public function authorizeChatChannel(User $user, int $roomId): bool
    {
        return ChatRoom::where('id', $roomId)
            ->whereHas('participants', fn ($q) => $q->where('user_id', $user->id))
            ->exists();
    }

    public function authorizePropertyChannel(User $user, int $propertyId): array|bool
    {
        $property = \Modules\RealEstate\Entities\Property::find($propertyId);

        if (! $property) {
            return false;
        }

        return [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'user_avatar' => $user->getFirstMediaUrl('avatar'),
        ];
    }

    public function canAccessRoom(int $roomId, int $userId): bool
    {
        return ChatRoom::where('id', $roomId)
            ->whereHas('participants', fn ($q) => $q->where('user_id', $userId))
            ->exists();
    }
}