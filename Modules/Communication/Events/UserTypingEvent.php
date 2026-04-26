<?php

namespace Modules\Communication\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Auth\Entities\User;

class UserTypingEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly int $roomId,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chat.' . $this->roomId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'user.typing';
    }

    public function broadcastWith(): array
    {
        try {
            return [
                'user_id' => $this->user->id,
                'user_name' => $this->user->name,
                'is_typing' => true,
            ];
        } catch (\Throwable $e) {
            return [
                'user_id' => $this->user->id,
                'user_name' => 'Unknown',
                'is_typing' => true,
            ];
        }
    }

    public function broadcastQueue(): string
    {
        return 'broadcast';
    }
}