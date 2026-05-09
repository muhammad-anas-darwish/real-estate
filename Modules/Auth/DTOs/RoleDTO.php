<?php

namespace Modules\Auth\DTOs;

use App\Interfaces\DTOInterface;

final readonly class RoleDTO implements DTOInterface
{
    public function __construct(
        public ?string $name = null,
        public ?string $guard_name = null,
        public ?array $permissions = [],
    ) {}

    public static function fromRequest(array $array): self
    {
        return new self(
            name: $array['name'] ?? null,
            guard_name: $array['guard_name'] ?? 'web',
            permissions: $array['permissions'] ?? [],
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'guard_name' => $this->guard_name,
        ], fn ($value) => $value !== null);
    }
}
