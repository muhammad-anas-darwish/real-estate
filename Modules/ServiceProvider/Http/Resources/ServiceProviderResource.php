<?php

namespace Modules\ServiceProvider\Http\Resources;

use App\Http\Resources\BaseJsonResource;
use Modules\Auth\Http\Resources\UserResource;
use Modules\Core\TemporaryFile\Http\Resources\TemporaryFileResource;

class ServiceProviderResource extends BaseJsonResource
{
    protected function getRelationMap(): array
    {
        return [
            'user' => UserResource::class,
            'coverageAreas' => ServiceProviderCoverageAreaResource::class,
            'media' => TemporaryFileResource::class,
        ];
    }

    protected function getCustomData(): array
    {
        return [
            'user_id' => $this->user_id,
            'type' => $this->type,
            'bio' => $this->bio,
            'experience_years' => $this->experience_years,
            'license_number' => $this->license_number,
            'price_type' => $this->price_type,
            'price_per_task' => $this->price_per_task,
            'is_verified' => $this->is_verified,
            'is_available' => $this->is_available,
            'average_rating' => $this->average_rating,
            'total_completed_tasks' => $this->total_completed_tasks,
            'license_document_url' => $this->license_document_url,
            'reviews_count' => $this->whenHas('reviews_count'),
            'metadata' => $this->metadata,
        ];
    }
}
