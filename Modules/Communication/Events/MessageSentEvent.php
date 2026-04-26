<?php

namespace Modules\Communication\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Communication\Entities\Message;

class MessageSentEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Message $message,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chat.' . $this->message->room_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    public function broadcastWith(): array
    {
        $sender = null;
        $type = null;
        $createdAt = null;
        
        try {
            if ($this->message->relationLoaded('sender')) {
                $sender = $this->message->sender;
            }
            if ($this->message->type) {
                $type = $this->message->type instanceof \Modules\Communication\Enums\MessageTypeEnum 
                    ? $this->message->type->value 
                    : (is_string($this->message->type) ? $this->message->type : null);
            }
            if ($this->message->created_at) {
                $createdAt = $this->message->created_at instanceof \Carbon\Carbon 
                    ? $this->message->created_at->toIso8601String() 
                    : date('c');
            }
        } catch (\Throwable $e) {
            // Ignore relation loading errors
        }
        
        return [
            'message_id' => $this->message->id,
            'room_id' => $this->message->room_id,
            'sender' => [
                'id' => $sender?->id,
                'name' => $sender?->name ?? 'Unknown',
            ],
            'body' => $this->message->body,
            'type' => $type,
            'created_at' => $createdAt,
        ];
    }
}