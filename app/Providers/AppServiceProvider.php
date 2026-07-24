<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $modulesPath = base_path('Modules');

        if (File::exists($modulesPath)) {
            $migrationPaths = glob($modulesPath.'/*/SubModules/*/Database/Migrations');
            $this->loadMigrationsFrom($migrationPaths);
            $this->loadMigrationsFrom(glob($modulesPath.'/*/Database/Migrations'));
        }

        $this->configureRateLimiters();
    }

    protected function configureRateLimiters(): void
    {
        RateLimiter::for('map', function (Request $request) {
            return Limit::perMinute(120)->by($request->ip());
        });
    }
}
