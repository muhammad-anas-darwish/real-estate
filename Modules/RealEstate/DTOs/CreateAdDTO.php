<?php

namespace Modules\RealEstate\DTOs;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final readonly class CreateAdDTO
{
    public function __construct(
        public ?string $title = null,
        public ?string $description = null,
        public ?string $mediaType = null,
        public ?string $externalUrl = null,
        public ?int $propertyId = null,
        public ?int $adGroupId = null,
        public ?string $status = 'draft',
        public ?string $startDate = null,
        public ?string $endDate = null,
        public ?array $media = [],
    ) {}

    public static function fromRequest(Request $request): self
    {
        $mediaItemRules = $request->input('media_type') === 'video'
            ? ['mimes:mp4,mov,webm', 'max:102400']
            : ['mimes:jpg,jpeg,png,webp', 'max:5120'];

        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'media_type' => ['required', 'string', 'in:video,image'],
            'external_url' => ['nullable', 'url', 'max:2048'],
            'property_id' => ['nullable', 'integer', 'exists:properties,id'],
            'ad_group_id' => ['nullable', 'integer', 'exists:ad_groups,id'],
            'status' => ['nullable', 'string', 'in:draft,active,paused,archived'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'media' => ['nullable', 'array'],
            'media.*' => $mediaItemRules,
        ];

        if ($request->input('media_type') === 'image') {
            $rules['media'][] = 'max:10';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $validated = $validator->validated();

        $media = [];
        if ($request->hasFile('media')) {
            foreach ($request->file('media') as $file) {
                $media[] = $file;
            }
        } elseif (! empty($validated['media'])) {
            $media = $validated['media'];
        }

        return new self(
            title: $validated['title'],
            description: $validated['description'] ?? null,
            mediaType: $validated['media_type'],
            externalUrl: $validated['external_url'] ?? null,
            propertyId: $validated['property_id'] ?? null,
            adGroupId: $validated['ad_group_id'] ?? null,
            status: $validated['status'] ?? 'draft',
            startDate: $validated['start_date'] ?? null,
            endDate: $validated['end_date'] ?? null,
            media: $media,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'title' => $this->title,
            'description' => $this->description,
            'media_type' => $this->mediaType,
            'external_url' => $this->externalUrl,
            'property_id' => $this->propertyId,
            'ad_group_id' => $this->adGroupId,
            'status' => $this->status,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'media' => $this->media,
        ], fn ($value) => $value !== null);
    }
}
