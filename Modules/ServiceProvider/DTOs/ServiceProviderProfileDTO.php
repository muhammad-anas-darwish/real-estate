<?php

namespace Modules\ServiceProvider\DTOs;

use App\Interfaces\DTOInterface;

final readonly class ServiceProviderProfileDTO implements DTOInterface
{
    public function __construct(
        public ?string $type = null,
        public ?string $bio = null,
        public ?int $experience_years = null,
        public ?string $license_number = null,
        public ?string $price_type = null,
        public ?float $price_per_task = null,
        public ?array $coverage_city_ids = null,
        public ?array $license_document = null,
        public ?array $metadata = null,
    ) {}

    public static function fromRequest(array $array): self
    {
        return new self(
            type: $array['type'] ?? null,
            bio: $array['bio'] ?? null,
            experience_years: $array['experience_years'] ?? null,
            license_number: $array['license_number'] ?? null,
            price_type: $array['price_type'] ?? null,
            price_per_task: $array['price_per_task'] ?? null,
            coverage_city_ids: $array['coverage_city_ids'] ?? null,
            license_document: $array['license_document'] ?? null,
            metadata: $array['metadata'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'type' => $this->type,
            'bio' => $this->bio,
            'experience_years' => $this->experience_years,
            'license_number' => $this->license_number,
            'price_type' => $this->price_type,
            'price_per_task' => $this->price_per_task,
            'metadata' => $this->metadata,
        ], fn ($value) => $value !== null);
    }
}
