<?php

namespace Modules\FileSystem\Listeners;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\FileSystem\Entities\StorageLimit;
use Modules\FileSystem\Entities\UserFolder;
use Modules\RealEstate\Events\PropertyDeleting;

class DeletePropertyFolder
{
    public function handle(PropertyDeleting $event): void
    {
        $property = $event->property;

        $folder = UserFolder::where('source_type', 'Property')
            ->where('source_id', $property->id)
            ->first();

        if (! $folder) {
            return;
        }

        DB::transaction(function () use ($folder) {
            $this->deleteFolderRecursive($folder);
        });
    }

    private function deleteFolderRecursive(UserFolder $folder): void
    {
        foreach ($folder->children as $child) {
            $this->deleteFolderRecursive($child);
        }

        $totalSize = 0;
        foreach ($folder->files as $file) {
            if ($file->isImage() && $file->file_path) {
                Storage::disk('public')->delete($file->file_path);
            }
            $totalSize += $file->size;
            $file->delete();
        }

        if ($totalSize > 0) {
            StorageLimit::where('user_id', $folder->user_id)
                ->decrement('used_bytes', $totalSize);
        }

        $folder->delete();
    }
}
