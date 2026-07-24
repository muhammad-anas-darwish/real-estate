<?php

namespace Modules\Ai\Providers;

use Illuminate\Support\ServiceProvider;

class AiServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
    }
}
