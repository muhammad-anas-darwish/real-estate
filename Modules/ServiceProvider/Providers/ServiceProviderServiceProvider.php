<?php

namespace Modules\ServiceProvider\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Modules\ServiceProvider\Entities\ServiceProviderProfile;
use Modules\ServiceProvider\Policies\ServiceProviderPolicy;

class ServiceProviderServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register module services
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $this->registerPolicies([
            ServiceProviderProfile::class => ServiceProviderPolicy::class,
        ]);
    }
}
