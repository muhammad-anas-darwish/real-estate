<?php

namespace Modules\Communication\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Auth\Entities\User;

class CleanReadNotificationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 120;

    public function handle(): void
    {
        $days = config('communication.jobs.cleanup_read_days', 30);
        $cutoffDate = now()->subDays($days);

        $deleted = 0;

        User::chunk(100, function ($users) use ($cutoffDate, &$deleted) {
            foreach ($users as $user) {
                $count = $user->notifications()
                    ->whereNotNull('read_at')
                    ->where('created_at', '<', $cutoffDate)
                    ->delete();

                $deleted += $count;
            }
        });

        Log::info("Cleaned {$deleted} old read notifications");
    }
}
