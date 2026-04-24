<?php

namespace Modules\Communication\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Auth\Entities\User;
use Modules\Communication\Notifications\BaseNotification;
use Modules\Communication\Services\Fcm\FcmService;
use Modules\Communication\Services\Fcm\FcmPayload;
use Modules\Communication\Enums\NotificationTypeEnum;

class SendFcmNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 10;
    public int $timeout = 30;

    public function __construct(
        public readonly User $user,
        public readonly BaseNotification $notification,
        public readonly ?string $customTitle = null,
        public readonly ?string $customBody = null,
    ) {
        $this->onQueue(config('communication.queue.notifications_high', 'notifications-high'));
    }

    public function handle(FcmService $fcmService): void
    {
        $payload = new FcmPayload(
            title: $this->customTitle ?? $this->notification->getTitle(),
            body: $this->customBody ?? $this->notification->getBody(),
            data: $this->notification->toFcmData(),
        );

        $result = $fcmService->sendToUser($this->user, $payload);

        if ($result->hasFailures()) {
            Log::warning('FCM notification job failed', [
                'user_id' => $this->user->id,
                'notification' => get_class($this->notification),
                'failures' => $result->failureCount,
            ]);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('FCM notification job permanently failed', [
            'user_id' => $this->user->id,
            'notification' => get_class($this->notification),
            'error' => $exception->getMessage(),
        ]);

        $admins = User::role('admin')->get();

        foreach ($admins as $admin) {
            $admin->notify(new \Modules\Communication\Notifications\AdminAlertNotification(
                'FCM Queue Failed',
                "Failed to send notification to user {$this->user->id}: " . $exception->getMessage()
            ));
        }
    }
}