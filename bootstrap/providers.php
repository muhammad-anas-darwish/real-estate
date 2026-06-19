<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\HorizonServiceProvider::class,
    App\Providers\TelescopeServiceProvider::class,
    Modules\Auth\Providers\FortifyServiceProvider::class,
    Modules\Communication\Providers\BroadcastServiceProvider::class,
    Modules\Communication\Providers\CommunicationServiceProvider::class,
    Modules\Communication\Providers\EventServiceProvider::class,
    Modules\Core\Category\Providers\CategoryServiceProvider::class,
    Modules\Core\SubModules\Location\Providers\LocationServiceProvider::class,
    Modules\Ledger\Providers\LedgerServiceProvider::class,
    Modules\RealEstate\Providers\RealEstateServiceProvider::class,
    Modules\ServiceProvider\Providers\ServiceProviderServiceProvider::class,
    Modules\Subscription\Providers\SubscriptionServiceProvider::class,
];
