<?php

namespace Modules\Communication\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Communication\Entities\Message;
use Modules\Communication\Events\MessageSentEvent;
use Pusher\Pusher;

class BroadcastChatMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public readonly Message $message
    ) {
        $this->onQueue(config('communication.queue.chat', 'chat'));
    }

    public function handle(): void
    {
        $message = $this->message->refresh();

        if ($message->trashed()) {
            Log::warning('BroadcastChatMessageJob: message was deleted', ['message_id' => $message->id]);
            return;
        }

        $message->loadMissing('sender');

        if (! $message->room_id) {
            Log::warning('BroadcastChatMessageJob: message has no room_id', ['message_id' => $message->id]);
            return;
        }

        $event = new MessageSentEvent($message);
        $channel = 'private-chat.' . $message->room_id;

        $config = config('broadcasting.connections.pusher');
        $pusher = new Pusher(
            $config['key'],
            $config['secret'],
            $config['app_id'],
            $config['options'] ?? []
        );

        $pusher->trigger($channel, 'message.sent', $event->broadcastPayload());
    }

    public function failed(mixed $exception): void
    {
        $message = 'Broadcast message failed';

        logger()->error($message, [
            'message_id' => $this->message->id,
            'error' => is_object($exception) && method_exists($exception, 'getMessage') ? $exception->getMessage() : 'Unknown error',
        ]);
    }
}
