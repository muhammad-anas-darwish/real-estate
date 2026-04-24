<?php

namespace Modules\Communication\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Communication\Events\MessageSentEvent;
use Modules\Communication\Events\NotificationReceivedEvent;
use Modules\Communication\Events\UserTypingEvent;
use Modules\Communication\Jobs\BroadcastChatMessageJob;
use Modules\Communication\Jobs\CleanExpiredFcmTokensJob;
use Modules\Communication\Jobs\SendFcmNotificationJob;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        MessageSentEvent::class => [
            BroadcastChatMessageJob::class,
        ],
        NotificationReceivedEvent::class => [
            SendFcmNotificationJob::class,
        ],
    ];

    public function boot(): void
    {
        //
    }
}