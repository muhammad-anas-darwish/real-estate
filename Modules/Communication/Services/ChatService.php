<?php

namespace Modules\Communication\Services;

use Illuminate\Support\Facades\DB;
use Modules\Auth\Entities\User;
use Modules\Communication\Entities\ChatRoom;
use Modules\Communication\Entities\Message;
use Modules\Communication\Enums\MessageTypeEnum;
use Modules\Communication\Enums\RoomTypeEnum;
use Modules\Communication\Events\MessageSentEvent;
use Modules\Communication\Exceptions\ChatAuthorizationException;
use Modules\RealEstate\Entities\Property;
use Modules\Communication\Notifications\NewMessageNotification;

class ChatService
{
    public function __construct(
        protected readonly UserPresenceService $presenceService,
        protected readonly NotificationService $notificationService,
    ) {}

    public function startPrivateChat(User $initiator, User $recipient): ChatRoom
    {
        $existingRoom = $this->getExistingPrivateRoom($initiator->id, $recipient->id);

        if ($existingRoom) {
            return $existingRoom;
        }

        return DB::transaction(function () use ($initiator, $recipient) {
            $room = ChatRoom::create([
                'type' => RoomTypeEnum::PRIVATE,
                'property_id' => null,
            ]);

            $room->participants()->attach([
                $initiator->id => ['joined_at' => now()],
                $recipient->id => ['joined_at' => now()],
            ]);

            return $room;
        });
    }

    public function startPropertyChat(User $user, Property $property): ChatRoom
    {
        $agentId = $property->publisher_id;

        if ($user->id === $agentId) {
            throw new ChatAuthorizationException('Cannot start chat with yourself');
        }

        $existingRoom = $this->getExistingPropertyRoom($property->id, $user->id, $agentId);

        if ($existingRoom) {
            return $existingRoom;
        }

        return DB::transaction(function () use ($user, $property, $agentId) {
            $room = ChatRoom::create([
                'type' => RoomTypeEnum::PRIVATE,
                'property_id' => $property->id,
            ]);

            $room->participants()->attach([
                $user->id => ['joined_at' => now()],
                $agentId => ['joined_at' => now()],
            ]);

            return $room;
        });
    }

    public function sendMessage(User $sender, int $roomId, array $data): Message
    {
        $this->authorizeParticipant($sender, $roomId);

        return DB::transaction(function () use ($sender, $roomId, $data) {
            $message = Message::create([
                'room_id' => $roomId,
                'sender_id' => $sender->id,
                'body' => $data['body'],
                'type' => MessageTypeEnum::tryFrom($data['type'] ?? 'text') ?? MessageTypeEnum::TEXT,
                'parent_id' => $data['parent_id'] ?? null,
            ]);

            $this->updateLastRead($roomId, $sender->id);

            try {
                $this->broadcastMessage($message);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Broadcast failed: ' . $e->getMessage());
            }

            $this->notifyOfflineParticipants($message);

            return $message;
        });
    }

    public function deleteMessage(User $user, int $messageId): void
    {
        $message = Message::findOrFail($messageId);

        if ($message->sender_id !== $user->id) {
            throw new ChatAuthorizationException('Only message sender can delete');
        }

        $message->delete();
    }

    public function markAsRead(int $roomId, User $user): void
    {
        $this->authorizeParticipant($user, $roomId);

        $this->updateLastRead($roomId, $user->id);
    }

    public function getRoomMessages(int $roomId, User $user, int $perPage = 50)
    {
        $this->authorizeParticipant($user, $roomId);

        return Message::where('room_id', $roomId)
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function getRoomsForUser(User $user)
    {
        return ChatRoom::whereHas('participants', fn($q) => $q->where('user_id', $user->id))
            ->with(['lastMessage', 'participants' => fn($q) => $q->select('users.id', 'name')])
            ->orderByDesc('created_at')
            ->paginate(15);
    }

    public function getRoom(int $roomId, User $user): ChatRoom
    {
        $this->authorizeParticipant($user, $roomId);

        return ChatRoom::with(['participants:id,name', 'property'])
            ->findOrFail($roomId);
    }

    protected function getExistingPrivateRoom(int $userId1, int $userId2): ?ChatRoom
    {
        $rooms = ChatRoom::where('type', RoomTypeEnum::PRIVATE)
            ->whereNull('property_id')
            ->whereHas('participants', fn($q) => $q->where('user_id', $userId1))
            ->get();

        foreach ($rooms as $room) {
            $participants = $room->participants->pluck('id')->toArray();

            if (in_array($userId1, $participants) && in_array($userId2, $participants)) {
                return $room;
            }
        }

        return null;
    }

    protected function getExistingPropertyRoom(int $propertyId, int $userId, int $agentId): ?ChatRoom
    {
        return ChatRoom::where('property_id', $propertyId)
            ->whereHas('participants', fn($q) => $q->where('user_id', $userId))
            ->whereHas('participants', fn($q) => $q->where('user_id', $agentId))
            ->first();
    }

    protected function authorizeParticipant(User $user, int $roomId): void
    {
        $isParticipant = ChatRoom::where('id', $roomId)
            ->whereHas('participants', fn($q) => $q->where('user_id', $user->id))
            ->exists();

        if (! $isParticipant) {
            throw new ChatAuthorizationException('Not a participant in this chat room');
        }
    }

    protected function broadcastMessage(Message $message): void
    {
        event(new MessageSentEvent($message));
    }

    protected function notifyOfflineParticipants(Message $message): void
    {
        try {
            $room = $message->room;
            $participants = $room->participants;

            foreach ($participants as $participant) {
                if ($participant->id === $message->sender_id) {
                    continue;
                }

                if (! $this->presenceService->isOnline($participant->id)) {
                    try {
                        $participant->notify(new NewMessageNotification(
                            senderName: $message->sender->name ?? 'Unknown',
                            messagePreview: substr($message->body ?? '', 0, 50),
                            roomId: $room->id,
                        ));
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::warning('Notification failed: ' . $e->getMessage());
                    }
                }
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('_notifyOfflineParticipants: ' . $e->getMessage());
        }
    }

    protected function updateLastRead(int $roomId, int $userId): void
    {
        DB::table('chat_room_participants')
            ->where('room_id', $roomId)
            ->where('user_id', $userId)
            ->update(['last_read_at' => now()]);
    }
}