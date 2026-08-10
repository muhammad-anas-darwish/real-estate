<?php

namespace Modules\FileSystem\Http\Resources;

use App\Http\Resources\BaseJsonResource;

class FolderResource extends BaseJsonResource
{
    protected function getCustomData(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'folder_type' => $this->folder_type,
            'is_protected' => $this->is_protected,
            'parent_id' => $this->parent_id,
            'can_move' => $this->isMovable(),
            'can_delete' => ! $this->is_protected,
            'can_rename' => $this->isMovable(),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }

    protected function getRelationMap(): array
    {
        return [
            'children' => self::class,
            'parent' => self::class,
            'user' => \Modules\Auth\Http\Resources\UserResource::class,
        ];
    }

    protected function getCounts(): array
    {
        return [
            'children_count' => $this->whenCounted('children'),
            'files_count' => $this->whenCounted('files'),
        ];
    }
}
