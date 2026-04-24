<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Console\Scheduling\Schedule;
use Modules\Communication\Jobs\CleanExpiredFcmTokensJob;
use Modules\Communication\Jobs\CleanReadNotificationsJob;

Artisan::command('inspire', function () {
    $this->line(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('communication:clean-fcm-tokens', function () {
    $deleted = app(\Modules\Communication\Services\Fcm\FcmTokenService::class)
        ->removeOldTokens(60);

    $this->info("Removed {$deleted} expired FCM tokens.");
})->purpose('Clean expired FCM tokens older than 60 days');

Schedule::job(new CleanExpiredFcmTokensJob(60))
    ->weekly()
    ->sundays()
    ->at('03:00');

Schedule::job(new CleanReadNotificationsJob())
    ->daily()
    ->at('02:00');