<?php

namespace Modules\Auth\DTOs;

use App\Interfaces\DTOInterface;

final readonly class UpdateProfileDTO implements DTOInterface
{
    public function __construct(
        public ?string $name = null,
        public ?string $phone = null,
        public ?string $website_url = null,
        public ?array $social_links = null,
        public ?string $description = null,
        public ?int $employees_count = null,
        public ?array $avatar = null,
    ) {}

    public static function fromRequest(array $array): self
    {
        return new self(
            name: $array['name'] ?? null,
            phone: $array['phone'] ?? null,
            website_url: $array['website_url'] ?? null,
            social_links: $array['social_links'] ?? null,
            description: $array['description'] ?? null,
            employees_count: $array['employees_count'] ?? null,
            avatar: $array['avatar'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'phone' => $this->phone,
            'website_url' => $this->website_url,
            'social_links' => $this->social_links,
            'description' => $this->description,
            'employees_count' => $this->employees_count,
        ], fn ($value) => $value !== null);
    }
}
