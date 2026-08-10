<?php

namespace Modules\FileSystem\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use Modules\Auth\Entities\User;
use Modules\FileSystem\Listeners\CreateGeneralFolder;
use Modules\FileSystem\Listeners\CreatePropertyFolder;
use Modules\FileSystem\Listeners\DeletePropertyFolder;
use Modules\FileSystem\Policies\FilePolicy;
use Modules\FileSystem\Policies\FolderPolicy;
use Modules\RealEstate\Events\PropertyCreated;
use Modules\RealEstate\Events\PropertyDeleting;

class FileSystemServiceProvider extends ServiceProvider
{
    protected $policies = [
        \Modules\FileSystem\Entities\UserFolder::class => FolderPolicy::class,
        \Modules\FileSystem\Entities\UserFile::class => FilePolicy::class,
    ];

    public function register(): void {}

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->registerPolicies();

        Event::listen(PropertyCreated::class, CreatePropertyFolder::class);
        Event::listen(PropertyDeleting::class, DeletePropertyFolder::class);

        User::created(fn (User $user) => app(CreateGeneralFolder::class)->handle($user));
    }
}
