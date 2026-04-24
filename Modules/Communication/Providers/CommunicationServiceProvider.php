<?php

namespace Modules\Communication\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Modules\Communication\Entities\Conversation;
use Modules\Communication\Entities\Message;
use Modules\Communication\Policies\ConversationPolicy;

class CommunicationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $this->registerPolicies([
            Conversation::class => ConversationPolicy::class,
        ]);
    }
}