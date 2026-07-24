<?php

namespace Modules\FileSystem\Listeners;

use Modules\FileSystem\Entities\UserFolder;
use Modules\RealEstate\Events\PropertyCreated;

class CreatePropertyFolder
{
    public function handle(PropertyCreated $event): void
    {
        $property = $event->property;
        $userId = $property->publisher_id;

        $folderName = $property->title
            ? __('messages.property_folder_name', ['id' => $property->id, 'title' => $property->title])
            : __('messages.property_folder_name_no_title', ['id' => $property->id]);

        UserFolder::create([
            'user_id' => $userId,
            'parent_id' => null,
            'name' => $folderName,
            'folder_type' => 'property',
            'source_type' => 'Property',
            'source_id' => $property->id,
            'is_protected' => true,
        ]);
    }
}
