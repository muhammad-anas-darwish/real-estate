<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Modules\Communication\Jobs\CleanExpiredFcmTokensJob;
use Modules\Communication\Jobs\CleanReadNotificationsJob;

Artisan::command('inspire', function () {
    $this->line(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('communication:clean-fcm-tokens', function () {
    $deleted = app(\Modules\Communication\Services\Fcm\FcmTokenService::class)
        ->removeOldTokens(60);

    $this->info("Removed {$deleted} expired FCM tokens.");
})->purpose('Clean expired FCM tokens older than 60 days')
  ->weekly()
  ->sundays()
  ->at('03:00');

Artisan::command('communication:clean-notifications', function () {
    $days = 30;
    $cutoffDate = now()->subDays($days);
    $deleted = 0;

    \Modules\Auth\Entities\User::chunk(100, function ($users) use ($cutoffDate, &$deleted) {
        foreach ($users as $user) {
            $count = $user->notifications()
                ->whereNotNull('read_at')
                ->where('created_at', '<', $cutoffDate)
                ->delete();
            $deleted += $count;
        }
    });

    $this->info("Cleaned {$deleted} old read notifications.");
})->purpose('Clean old read notifications')
  ->daily()
  ->at('02:00');