<?php

namespace Modules\Auth\DTOs;

use App\Interfaces\DTOInterface;

final readonly class UserDTO implements DTOInterface
{
    public function __construct(
        public ?string $name = null,
        public ?string $email = null,
        public ?string $password = null,
        public ?string $status = null,
        public ?string $publisher_type = null,
        public ?string $phone = null,
        public ?string $website_url = null,
        public ?array $social_links = null,
        public ?string $description = null,
        public ?bool $is_verified = null,
        public ?int $employees_count = null,
        public ?string $contact_preference = null,
        public ?array $role_ids = [],
    ) {}

    public static function fromRequest(array $array): self
    {
        return new self(
            name: $array['name'] ?? null,
            email: $array['email'] ?? null,
            password: $array['password'] ?? null,
            status: $array['status'] ?? null,
            publisher_type: $array['publisher_type'] ?? null,
            phone: $array['phone'] ?? null,
            website_url: $array['website_url'] ?? null,
            social_links: $array['social_links'] ?? null,
            description: $array['description'] ?? null,
            is_verified: $array['is_verified'] ?? null,
            employees_count: $array['employees_count'] ?? null,
            contact_preference: $array['contact_preference'] ?? null,
            role_ids: $array['role_ids'] ?? [],
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
            'status' => $this->status,
            'publisher_type' => $this->publisher_type,
            'phone' => $this->phone,
            'website_url' => $this->website_url,
            'social_links' => $this->social_links,
            'description' => $this->description,
            'is_verified' => $this->is_verified,
            'employees_count' => $this->employees_count,
            'contact_preference' => $this->contact_preference,
        ], fn ($value) => $value !== null);
    }
}
