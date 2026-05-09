<?php

namespace Modules\Communication\Events;

<?php

namespace Modules\Communication\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Modules\Communication\Entities\Message;

class MessageSentEvent
{
    use Dispatchable;

    public function __construct(
        public readonly Message $message,
    ) {}

    public function broadcastPayload(): array
    {
        $sender = $this->message->relationLoaded('sender')
            ? $this->message->sender
            : null;

        return [
            'message_id' => $this->message->id,
            'room_id' => $this->message->room_id,
            'sender' => [
                'id' => $sender?->id,
                'name' => $sender?->name ?? 'Unknown',
            ],
            'body' => $this->message->body,
            'type' => $this->message->type?->value ?? (is_string($this->message->type) ? $this->message->type : null),
            'created_at' => $this->message->created_at?->toIso8601String() ?? now()->toIso8601String(),
        ];
    }
}
