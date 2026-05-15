<?php

namespace Modules\Core\Contracts\Chat;

use Illuminate\Pagination\LengthAwarePaginator;

interface ChatRepositoryInterface
{
    public function findOrCreatePrivateRoom(int $userId1, int $userId2): \Modules\Communication\Entities\Chat\Conversation;

    public function getRoomsForUser(int $userId): LengthAwarePaginator;

    public function getMessagesPaginated(int $roomId, int $perPage): LengthAwarePaginator;

    public function markAsRead(int $roomId, int $userId): void;
}
