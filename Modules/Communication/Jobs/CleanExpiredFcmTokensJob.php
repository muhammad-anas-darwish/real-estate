<?php

namespace Modules\Communication\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Communication\Services\Fcm\FcmTokenService;

class CleanExpiredFcmTokensJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected readonly int $days = 60
    ) {}

    public function handle(FcmTokenService $tokenService): void
    {
        $deleted = $tokenService->removeOldTokens($this->days);

        if ($deleted > 0) {
            logger()->info("Cleaned {$deleted} expired FCM tokens");
        }
    }
}