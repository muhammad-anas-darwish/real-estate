<?php

namespace Modules\Auth\Http\Resources;

use App\Http\Resources\BaseJsonResource;
use Modules\Core\TemporaryFile\Http\Resources\TemporaryFileResource;

class OfficeProfileResource extends BaseJsonResource
{
    protected function getRelationMap(): array
    {
        return [
            'media' => TemporaryFileResource::class,
        ];
    }

    protected function getCustomData(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'website_url' => $this->website_url,
            'social_links' => $this->social_links,
            'description' => $this->description,
            'is_verified' => $this->is_verified,
            'employees_count' => $this->employees_count,
            'contact_preference' => $this->contact_preference,
            'average_rating' => $this->average_rating,
            'reviews_count' => $this->whenHas('reviews_count'),
            'avatar_url' => $this->avatar_url,
            'license_document_url' => $this->license_document_url,
        ];
    }
}
