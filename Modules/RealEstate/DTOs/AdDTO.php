<?php

namespace Modules\RealEstate\DTOs;

use Carbon\Carbon;
use Modules\RealEstate\Entities\Ad;

final readonly class AdDTO
{
    public function __construct(
        public ?int $id = null,
        public ?int $adGroupId = null,
        public ?string $title = null,
        public ?string $description = null,
        public ?string $mediaType = null,
        public ?string $externalUrl = null,
        public ?int $propertyId = null,
        public ?string $status = null,
        public ?bool $isDefault = null,
        public ?string $startDate = null,
        public ?string $endDate = null,
        public ?int $createdBy = null,
        public ?array $media = [],
    ) {}

    public static function fromModel(Ad $ad): self
    {
        return new self(
            id: $ad->id,
            adGroupId: $ad->ad_group_id,
            title: $ad->title,
            description: $ad->description,
            mediaType: $ad->media_type?->value,
            externalUrl: $ad->external_url,
            propertyId: $ad->property_id,
            status: $ad->status?->value,
            isDefault: $ad->is_default,
            startDate: $ad->start_date?->format('Y-m-d H:i:s'),
            endDate: $ad->end_date?->format('Y-m-d H:i:s'),
            createdBy: $ad->created_by,
            media: $ad->relationLoaded('media')
                ? $ad->getMedia('ad_media')->map(fn ($m) => [
                    'id' => $m->id,
                    'url' => $m->getUrl(),
                    'thumb' => $m->getUrl('thumb'),
                    'medium' => $m->getUrl('medium'),
                    'mime_type' => $m->mime_type,
                ])->toArray()
                : [],
        );
    }

    public function hasActiveSchedule(): bool
    {
        if ($this->startDate === null) {
            return false;
        }

        $now = Carbon::now();
        $start = Carbon::parse($this->startDate);

        if ($this->endDate === null) {
            return $now >= $start;
        }

        $end = Carbon::parse($this->endDate);

        return $now >= $start && $now <= $end;
    }

    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'ad_group_id' => $this->adGroupId,
            'title' => $this->title,
            'description' => $this->description,
            'media_type' => $this->mediaType,
            'external_url' => $this->externalUrl,
            'property_id' => $this->propertyId,
            'status' => $this->status,
            'is_default' => $this->isDefault,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'created_by' => $this->createdBy,
            'media' => $this->media,
        ], fn ($value) => $value !== null);
    }
}
