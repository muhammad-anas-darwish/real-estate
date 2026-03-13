<?php

namespace App\Providers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $modulesPath = base_path('Modules');

        if (File::exists($modulesPath)) {
            // Pattern to find migrations in: Modules/{Main}/SubModules/{Sub}/Database/Migrations
            // We use glob to find all matching directories
            $migrationPaths = glob($modulesPath . '/*/SubModules/*/Database/Migrations');

            // Register each found path into the migrator
            $this->loadMigrationsFrom($migrationPaths);

            // If you also have migrations in the main module level: Modules/{Main}/Database/Migrations
            $this->loadMigrationsFrom(glob($modulesPath . '/*/Database/Migrations'));
        }
     }
}
