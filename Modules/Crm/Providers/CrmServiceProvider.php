<?php

namespace Modules\Crm\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Modules\Crm\Policies\LeadNotePolicy;
use Modules\Crm\Policies\LeadPolicy;

class CrmServiceProvider extends ServiceProvider
{
    protected $policies = [
        \Modules\Crm\Entities\Lead::class => LeadPolicy::class,
        \Modules\Crm\Entities\LeadNote::class => LeadNotePolicy::class,
    ];

    public function register(): void {}

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->registerPolicies();
    }
}
