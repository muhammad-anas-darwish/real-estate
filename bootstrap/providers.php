<?php

$providers = [
    App\Providers\AppServiceProvider::class,
];

if (env('HORIZON_ENABLED', false)) {
    $providers[] = App\Providers\HorizonServiceProvider::class;
}

if (env('TELESCOPE_ENABLED', false)) {
    $providers[] = App\Providers\TelescopeServiceProvider::class;
}

return array_merge($providers, [
    Modules\Auth\Providers\FortifyServiceProvider::class,
    Modules\Communication\Providers\BroadcastServiceProvider::class,
    Modules\Communication\Providers\CommunicationServiceProvider::class,
    Modules\Communication\Providers\EventServiceProvider::class,
    Modules\Core\Category\Providers\CategoryServiceProvider::class,
    Modules\Core\SubModules\Location\Providers\LocationServiceProvider::class,
    Modules\RealEstate\Providers\RealEstateServiceProvider::class,
]);
