<?php

namespace Modules\Subscription\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Subscription\Services\StripeWebhookService;

class ProcessStripeWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(
        public readonly string $eventId,
        public readonly string $eventType,
        public readonly array $payload
    ) {
        $this->onQueue('default');
    }

    public function handle(StripeWebhookService $webhookService): void
    {
        Log::info('Processing Stripe webhook event', [
            'event_id' => $this->eventId,
            'type' => $this->eventType,
        ]);

        $webhookService->processEvent($this->eventId, $this->eventType, $this->payload);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Stripe webhook job failed', [
            'event_id' => $this->eventId,
            'type' => $this->eventType,
            'error' => $exception->getMessage(),
        ]);
    }
}
