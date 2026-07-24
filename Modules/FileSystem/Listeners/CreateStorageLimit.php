<?php

namespace Modules\FileSystem\Listeners;

use Modules\Auth\Events\UserRegistered;
use Modules\FileSystem\Entities\StorageLimit;

class CreateStorageLimit
{
    public function handle(UserRegistered $event): void
    {
        StorageLimit::firstOrCreate(
            ['user_id' => $event->user->id],
            [
                'quota_bytes' => 104_857_600,
                'used_bytes' => 0,
                'package_type' => 'free',
                'package_expires_at' => null,
            ]
        );
    }
}
