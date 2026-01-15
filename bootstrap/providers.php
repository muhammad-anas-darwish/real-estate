<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\TelescopeServiceProvider::class,
    Modules\Auth\Providers\FortifyServiceProvider::class,
    Modules\RealEstate\Providers\RealEstateServiceProvider::class,
    Modules\Core\Category\Providers\CategoryServiceProvider::class,
];
