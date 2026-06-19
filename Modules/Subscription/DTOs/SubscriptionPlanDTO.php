<?php

namespace Modules\Subscription\DTOs;

use App\Interfaces\DTOInterface;

final readonly class SubscriptionPlanDTO implements DTOInterface
{
    public function __construct(
        public ?string $name = null,
        public ?string $slug = null,
        public ?string $description = null,
        public ?float $price = null,
        public ?string $currency = null,
        public ?int $duration_days = null,
        public ?bool $is_active = null,
        public ?int $sort_order = null,
    ) {}

    public static function fromRequest(array $array): self
    {
        return new self(
            name: $array['name'] ?? null,
            slug: $array['slug'] ?? null,
            description: $array['description'] ?? null,
            price: $array['price'] ?? null,
            currency: $array['currency'] ?? null,
            duration_days: $array['duration_days'] ?? null,
            is_active: $array['is_active'] ?? null,
            sort_order: $array['sort_order'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'price' => $this->price,
            'currency' => $this->currency,
            'duration_days' => $this->duration_days,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
        ], fn ($value) => $value !== null);
    }
}
