<?php

namespace Modules\FileSystem\Listeners;

use Modules\Auth\Entities\User;
use Modules\FileSystem\Entities\StorageLimit;
use Modules\FileSystem\Entities\UserFolder;

class CreateGeneralFolder
{
    public function handle(User $user): void
    {
        UserFolder::create([
            'user_id' => $user->id,
            'parent_id' => null,
            'name' => __('messages.general_folder_name'),
            'folder_type' => 'general',
            'is_protected' => true,
        ]);

        StorageLimit::firstOrCreate(
            ['user_id' => $user->id],
            [
                'quota_bytes' => 104_857_600,
                'used_bytes' => 0,
                'package_type' => 'free',
            ]
        );
    }
}
