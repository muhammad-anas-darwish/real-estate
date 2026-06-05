<?php

namespace Modules\RealEstate\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Modules\RealEstate\Entities\Ad;
use Modules\RealEstate\Entities\AdGroup;
use Modules\RealEstate\Entities\Property;
use Modules\RealEstate\Expert\Services\ExpertRelationshipService;
use Modules\RealEstate\Expert\Services\ExpertRequestService;
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
        $this->app->singleton(ExpertRequestService::class);
        $this->app->singleton(ExpertRelationshipService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
        $this->loadRoutesFrom(__DIR__.'/../Expert/Routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }
}
