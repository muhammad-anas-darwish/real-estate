<?php

namespace Modules\Communication\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Notification;
use Modules\Communication\Services\Chat\ConversationService;
use Modules\Communication\Services\ChatService;
use Modules\Communication\Services\UserPresenceService;
use Modules\Communication\Services\Broadcasting\PusherNotificationChannel;
use Modules\Communication\Services\Fcm\FcmService;
use Modules\Communication\Services\Fcm\FcmChannel;
use Modules\Communication\Services\Fcm\FcmTokenService;
use Modules\Communication\Services\NotificationPreferenceService;
use Modules\Communication\Services\NotificationService;
use Modules\Core\Contracts\Chat\ChatRepositoryInterface;
use Modules\Core\Contracts\Notification\NotificationChannelInterface;

class CommunicationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ChatRepositoryInterface::class, function ($app) {
            return new ConversationService();
        });

        $this->app->singleton(UserPresenceService::class);
        $this->app->singleton(ChatService::class, function ($app) {
            return new ChatService(
                $app->make(UserPresenceService::class),
                $app->make(NotificationService::class)
            );
        });

        $this->app->singleton(NotificationPreferenceService::class);
        $this->app->singleton(NotificationService::class, function ($app) {
            return new NotificationService(
                $app->make(NotificationPreferenceService::class)
            );
        });

        $this->app->singleton(FcmTokenService::class);
        $this->app->singleton(FcmService::class, function ($app) {
            return new FcmService(
                $app->make(\Kreait\Firebase\Messaging::class),
                $app->make(FcmTokenService::class)
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
                return $app->make(PusherNotificationChannel::class);
            });

            $channelManager->extend('fcm', function ($app) {
                return $app->make(FcmChannel::class);
            });
        });

        $this->registerPolicies();
    }
}