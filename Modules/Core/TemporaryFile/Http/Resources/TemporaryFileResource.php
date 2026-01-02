<?php

declare(strict_types = 1);

namespace Modules\Core\TemporaryFile\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Core\TemporaryFile\Entities\TemporaryFile;

/**
 * @property-read TemporaryFile $resource
 */
class TemporaryFileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->resource->id,
            "type" => $this->resource->type,
            "folder" => $this->resource->folder,
            "filename" => $this->resource->filename,
            "created_at" => $this->resource->created_at->toDateTimeString(),
        ];
    }
}
