<?php

namespace Modules\Subscription\DTOs;

use App\Interfaces\DTOInterface;

final readonly class SubscriptionFeatureDTO implements DTOInterface
{
    public function __construct(
        public ?string $name = null,
        public ?string $slug = null,
        public ?string $type = null,
        public ?string $description = null,
    ) {}

    public static function fromRequest(array $array): self
    {
        return new self(
            name: $array['name'] ?? null,
            slug: $array['slug'] ?? null,
            type: $array['type'] ?? null,
            description: $array['description'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'slug' => $this->slug,
            'type' => $this->type,
            'description' => $this->description,
        ], fn ($value) => $value !== null);
    }
}
