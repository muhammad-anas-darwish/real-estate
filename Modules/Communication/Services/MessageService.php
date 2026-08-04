<?php

namespace Modules\Communication\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Auth\Entities\User;
use Modules\Communication\Entities\ChatRoom;
use Modules\Communication\Entities\Message;

class MessageService
{
    public function replyToMessage(User $sender, int $parentId, array $data): Message
    {
        $parentMessage = Message::findOrFail($parentId);

        return Message::create([
            'room_id' => $parentMessage->room_id,
            'sender_id' => $sender->id,
            'body' => $data['body'],
            'type' => $data['type'] ?? 'text',
            'parent_id' => $parentId,
        ]);
    }

    public function markRoomAsRead(User $user, int $roomId): void
    {
        $this->authorizeParticipant($user, $roomId);

        Message::where('room_id', $roomId)
            ->where('sender_id', '!=', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $this->updateLastRead($roomId, $user->id);
    }

    public function getUnreadCountPerRoom(User $user): array
    {
        $rooms = ChatRoom::whereHas('participants', fn ($q) => $q->where('user_id', $user->id))
            ->with('participants')
            ->get();

        $result = [];

        foreach ($rooms as $room) {
            $lastReadAt = $room->participants
                ->firstWhere('id', $user->id)?->pivot->last_read_at;

            $unreadCount = Message::where('room_id', $room->id)
                ->where('sender_id', '!=', $user->id)
                ->when($lastReadAt, fn ($q) => $q->where('created_at', '>', $lastReadAt))
                ->count();

            $result[$room->id] = $unreadCount;
        }

        return $result;
    }

    public function getRoomMessages(int $roomId, User $user, int $perPage = 50): LengthAwarePaginator
    {
        $this->authorizeParticipant($user, $roomId);

        return Message::where('room_id', $roomId)
            ->with(['sender:id,name', 'parent:id,body,sender_id,created_at'])
            ->withExists(['parent as has_parent' => function ($query) use ($roomId) {
                $query->where('room_id', $roomId);
            }])
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function getMessagesWithReplies(int $roomId, User $user, int $perPage = 50): LengthAwarePaginator
    {
        $this->authorizeParticipant($user, $roomId);

        return Message::where('room_id', $roomId)
            ->with(['sender:id,name', 'replies.sender:id,name'])
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    protected function authorizeParticipant(User $user, int $roomId): void
    {
        $isParticipant = ChatRoom::where('id', $roomId)
            ->whereHas('participants', fn ($q) => $q->where('user_id', $user->id))
            ->exists();

        if (! $isParticipant) {
            throw new \Modules\Communication\Exceptions\ChatAuthorizationException('Not a participant');
        }
    }

    protected function updateLastRead(int $roomId, int $userId): void
    {
        \Illuminate\Support\Facades\DB::table('chat_room_participants')
            ->where('room_id', $roomId)
            ->where('user_id', $userId)
            ->update(['last_read_at' => now()]);
    }
}
