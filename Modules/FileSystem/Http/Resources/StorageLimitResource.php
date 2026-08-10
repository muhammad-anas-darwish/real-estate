<?php

namespace Modules\FileSystem\Http\Resources;

use App\Http\Resources\BaseJsonResource;

class StorageLimitResource extends BaseJsonResource
{
    protected function getCustomData(): array
    {
        return [
            'user_id' => $this->user_id,
            'quota_bytes' => $this->quota_bytes,
            'used_bytes' => $this->used_bytes,
            'remaining_bytes' => $this->remaining_bytes,
            'used_percentage' => $this->used_percentage,
            'package_type' => $this->package_type?->value,
            'package_expires_at' => $this->package_expires_at?->format('Y-m-d H:i:s'),
            'is_exceeded' => $this->isExceeded(),
            'is_near_limit' => $this->isNearLimit(),
        ];
    }
}
