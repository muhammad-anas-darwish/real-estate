<?php

namespace Modules\RealEstate\DTOs;

use Modules\RealEstate\Entities\Ad;

final readonly class AdDisplayDTO
{
    public function __construct(
        public ?int $id = null,
        public ?string $title = null,
        public ?string $description = null,
        public ?string $mediaType = null,
        public ?string $externalUrl = null,
        public ?array $mediaUrls = [],
        public ?int $propertyId = null,
        public ?bool $isLinkedToProperty = false,
    ) {}

    public static function fromAd(Ad $ad): self
    {
        return new self(
            id: $ad->id,
            title: $ad->title,
            description: $ad->description,
            mediaType: $ad->media_type?->value,
            externalUrl: $ad->external_url,
            mediaUrls: $ad->relationLoaded('media')
                ? $ad->getMedia('ad_media')->map(fn ($m) => $m->getUrl())->values()->toArray()
                : [],
            propertyId: $ad->property_id,
            isLinkedToProperty: $ad->relationLoaded('property')
                ? $ad->property !== null
                : false,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'media_type' => $this->mediaType,
            'external_url' => $this->externalUrl,
            'media_urls' => $this->mediaUrls,
            'property_id' => $this->propertyId,
            'is_linked_to_property' => $this->isLinkedToProperty,
        ], fn ($value) => $value !== null);
    }
}
