<?php

namespace Modules\RealEstate\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Modules\RealEstate\Entities\Ad;
use Modules\RealEstate\Entities\AdGroup;
use Modules\RealEstate\Entities\Property;
use Modules\RealEstate\Policies\AdGroupPolicy;
use Modules\RealEstate\Policies\AdPolicy;
use Modules\RealEstate\Policies\PropertyPolicy;

class RealEstateServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register module services
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $this->registerPolicies([
            Property::class => PropertyPolicy::class,
            AdGroup::class => AdGroupPolicy::class,
            Ad::class => AdPolicy::class,
        ]);
    }
}
