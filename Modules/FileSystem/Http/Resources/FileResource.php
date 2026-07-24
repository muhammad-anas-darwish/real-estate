<?php

namespace Modules\FileSystem\Http\Resources;

use App\Http\Resources\BaseJsonResource;

class FileResource extends BaseJsonResource
{
    protected function getCustomData(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'file_type' => $this->file_type?->value,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'size_readable' => $this->formatBytes($this->size),
            'content' => $this->when($this->isText(), $this->content),
            'preview_url' => $this->when($this->isImage(), fn () => $this->file_path ? url('storage/'.$this->file_path) : null
            ),
            'download_url' => $this->when($this->isImage(), fn () => $this->file_path ? url('storage/'.$this->file_path) : null
            ),
            'folder_id' => $this->folder_id,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }

    protected function getRelationMap(): array
    {
        return [
            'folder' => FolderResource::class,
            'user' => \Modules\Auth\Http\Resources\UserResource::class,
        ];
    }

    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        return round($bytes / (1024 ** $pow), $precision).' '.$units[$pow];
    }
}
