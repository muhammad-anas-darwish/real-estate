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
    Modules\RealEstate\Providers\RealEstateServiceProvider::class,
];
