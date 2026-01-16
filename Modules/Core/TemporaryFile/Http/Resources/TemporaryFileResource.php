<?php

declare(strict_types = 1);

namespace Modules\Core\TemporaryFile\Http\Resources;

use App\Http\Resources\BaseJsonResource;

class TemporaryFileResource extends BaseJsonResource
{
    protected function getRelationMap(): array
    {
        return [
            // Add relations here
        ];
    }

    protected function getCustomData(): array
    {
        return [
            'type' => $this->type,
            'folder' => $this->folder,
            'filename' => $this->filename,
        ];
    }
}
