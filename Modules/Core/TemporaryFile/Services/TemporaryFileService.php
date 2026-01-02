<?php

namespace Modules\Core\TemporaryFile\Services;

use Illuminate\Support\Facades\DB;
use Modules\Core\TemporaryFile\DTOs\TemporaryFileDTO;
use Modules\Core\TemporaryFile\Entities\TemporaryFile;
use Modules\Core\TemporaryFile\Helpers\UploadMediaHelper;

class TemporaryFileService
{
    public function store(TemporaryFileDTO $dto): array
    {
        return DB::transaction(function () use ($dto): array {

            $files = $dto->files ?? [$dto->file];

            return $this->storeFiles($files, $dto->type);

        });
    }

    private function storeFiles($files, $type): array
    {
        $storedFiles = [];

        foreach ($files as $file) {
            $filename = $file->getClientOriginalName();
            $folder = $this->generateUniqueFolder();

            $file->storeAs('files/tmp/' . $folder, $filename);

            $storedFiles[] = TemporaryFile::query()->create([
                'folder' => $folder,
                'filename' => $filename,
                'type' => $type,
            ]);
        }

        return $storedFiles;
    }

    private function generateUniqueFolder()
    {
        return uniqid() . '-' . now()->timestamp;
    }

    public function moveTemporaryFilesToMedia(array $folders, $model, $ruleName, string $collectionName = 'default'): void
    {
        $temporaryFiles = TemporaryFile::whereIn('folder', $folders)
            ->where('type', $ruleName)
            ->get();

        foreach ($temporaryFiles as $temporaryFile) {
            $temporaryFilePath = storage_path("app/files/tmp/{$temporaryFile->folder}/{$temporaryFile->filename}");

            if (file_exists($temporaryFilePath)) {

                UploadMediaHelper::upload(
                    new \Illuminate\Http\UploadedFile(
                        $temporaryFilePath,
                        $temporaryFile->filename
                    ),
                    $model,
                    $collectionName
                );

            }

            $temporaryFile->delete();
        }
    }
}
