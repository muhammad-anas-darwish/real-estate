<?php

namespace Modules\RealEstate\Http\Resources;

use App\Http\Resources\BaseJsonResource;
use Modules\Auth\Http\Resources\UserResource;

class ReviewResource extends BaseJsonResource
{
    protected function getRelationMap(): array
    {
        return [
            'reviewer' => UserResource::class,
            'property' => \Modules\RealEstate\Http\Resources\PropertyResource::class,
        ];
    }

    protected function getCustomData(): array
    {
        return [
            'reviewed_id' => $this->reviewed_id,
            'reviewer_id' => $this->reviewer_id,
            'property_id' => $this->property_id,
            'rating' => $this->rating,
            'comment' => $this->comment,
        ];
    }
}
