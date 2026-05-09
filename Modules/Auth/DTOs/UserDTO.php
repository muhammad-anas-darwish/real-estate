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
        public ?array $role_ids = [],
    ) {}

    public static function fromRequest(array $array): self
    {
        return new self(
            name: $array['name'] ?? null,
            email: $array['email'] ?? null,
            password: $array['password'] ?? null,
            status: $array['status'] ?? null,
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
        ], fn ($value) => $value !== null);
    }
}
