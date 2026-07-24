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
    Modules\Crm\Providers\CrmServiceProvider::class,
    Modules\FileSystem\Providers\FileSystemServiceProvider::class,
    Modules\Ledger\Providers\LedgerServiceProvider::class,
    Modules\RealEstate\Providers\RealEstateServiceProvider::class,
    Modules\ServiceProvider\Providers\ServiceProviderServiceProvider::class,
    Modules\Subscription\Providers\SubscriptionServiceProvider::class,
    Modules\Deposit\Providers\DepositServiceProvider::class,
    Modules\Statistics\Providers\StatisticsServiceProvider::class,
    Modules\Ai\Providers\AiServiceProvider::class,
];
