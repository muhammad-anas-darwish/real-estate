<?php

namespace Modules\Communication\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Communication\Services\Broadcasting\ChannelAuthService;

class BroadcastServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ChannelAuthService::class);
    }

    public function boot(): void
    {
        //
    }
}
