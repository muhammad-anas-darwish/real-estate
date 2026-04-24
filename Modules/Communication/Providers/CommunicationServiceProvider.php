<?php

namespace Modules\Communication\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Notification;
use Modules\Communication\Services\Chat\ConversationService;
use Modules\Communication\Services\Chat\MessageService;
use Modules\Communication\Services\Fcm\FcmService;
use Modules\Communication\Services\Fcm\FcmTokenService;
use Modules\Communication\Services\Notification\FcmPushService;
use Modules\Communication\Services\Notification\NotificationService;
use Modules\Communication\Services\Notification\PusherNotificationService;
use Modules\Core\Contracts\Chat\ChatRepositoryInterface;
use Modules\Core\Contracts\Notification\NotificationChannelInterface;
use Modules\Core\Contracts\Notification\NotificationServiceInterface;

class CommunicationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ChatRepositoryInterface::class, function ($app) {
            return new ConversationService();
        });

        $this->app->singleton(FcmTokenService::class);
        $this->app->singleton(FcmService::class, function ($app) {
            return new FcmService(
                $app->make(\Kreait\Firebase\Messaging::class),
                $app->make(FcmTokenService::class)
            );
        });

        $this->app->singleton(NotificationServiceInterface::class, function ($app) {
            return new NotificationService(
                $app->make(PusherNotificationService::class),
                $app->make(FcmPushService::class)
            );
        });

        $this->app->tag([
            NotificationChannelInterface::class,
        ], 'notification.channels');
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../Routes/chat.php');
        $this->loadRoutesFrom(__DIR__ . '/../Routes/notification.php');
        $this->loadRoutesFrom(__DIR__ . '/../Routes/fcm.php');
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');

        $this->mergeConfigFrom(
            __DIR__ . '/../Config/communication.php',
            'communication'
        );

        Notification::resolved(function (ChannelManager $channelManager) {
            $channelManager->extend('pusher', function ($app) {
                return $app->make(PusherNotificationService::class);
            });

            $channelManager->extend('fcm', function ($app) {
                return $app->make(FcmPushService::class);
            });
        });

        $this->registerPolicies();
    }
}