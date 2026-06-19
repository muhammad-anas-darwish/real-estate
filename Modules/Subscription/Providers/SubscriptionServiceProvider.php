<?php

namespace Modules\Subscription\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Modules\Subscription\Console\ExpireSubscriptionsCommand;
use Modules\Subscription\Console\NotifyExpiringSubscriptionsCommand;

class SubscriptionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../Config/subscription.php',
            'subscription'
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $this->registerMiddleware();
        $this->registerCommands();
        $this->registerScheduler();
    }

    protected function registerMiddleware(): void
    {
        $router = $this->app['router'];
        $router->aliasMiddleware('subscribed', \Modules\Subscription\Http\Middleware\EnsureActiveSubscription::class);
        $router->aliasMiddleware('feature', \Modules\Subscription\Http\Middleware\EnsureFeature::class);
    }

    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                ExpireSubscriptionsCommand::class,
                NotifyExpiringSubscriptionsCommand::class,
            ]);
        }
    }

    protected function registerScheduler(): void
    {
        $this->app->booted(function () {
            $schedule = $this->app[\Illuminate\Console\Scheduling\Schedule::class];

            $schedule->command('subscription:expire')->dailyAt('00:00');
            $schedule->command('subscription:notify-expiring --days=3')->dailyAt('09:00');
        });
    }
}
