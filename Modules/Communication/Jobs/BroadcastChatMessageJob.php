<?php

namespace Modules\Communication\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Communication\Entities\Message;
use Modules\Communication\Events\MessageSentEvent;

class BroadcastChatMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 0;

    public function __construct(
        public readonly Message $message
    ) {
        $this->onQueue(config('communication.queue.chat', 'chat'));
    }

    public function handle(): void
    {
        event(new MessageSentEvent($this->message));
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