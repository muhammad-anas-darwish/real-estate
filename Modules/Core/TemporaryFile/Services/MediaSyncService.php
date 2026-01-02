<?php

namespace Modules\Core\TemporaryFile\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class MediaSyncService
{
    public function __construct(
        protected TemporaryFileService $temporaryFileService
    ) {}

    public function syncFiles(
        Model $model,
        array $files,
        string $ruleName,
        string $collectionName = 'attachements'
    ): void {
        $filesCollection = collect($files);

        // 1. تنظيف الملفات المحذوفة
        $this->removeOldFiles($model, $filesCollection, $collectionName);

        // 2. معالجة الملفات الجديدة
        $this->processNewFiles($model, $filesCollection, $ruleName, $collectionName);
    }

    protected function removeOldFiles(Model $model, Collection $files, string $collectionName): void
    {
        $fileIdsToKeep = $files->pluck('id')->filter()->toArray();

        $model->media()
            ->where('collection_name', $collectionName)
            ->whereNotIn('id', $fileIdsToKeep)
            ->delete();
    }

    protected function processNewFiles(Model $model, Collection $files, string $ruleName, string $collectionName): void
    {
        $temporaryFolders = $files->pluck('temporary_folder')->filter()->toArray();

        if (!empty($temporaryFolders)) {
            $this->temporaryFileService->moveTemporaryFilesToMedia(
                folders: $temporaryFolders,
                model: $model,
                ruleName: $ruleName,
                collectionName: $collectionName
            );
        }
    }
}
