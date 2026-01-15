<?php

namespace Modules\Core\Category\DTOs;

use App\Interfaces\DTOInterface;

readonly final class CategoryDTO implements DTOInterface
{
    public function __construct(
        public ?string $name = null,
        public ?string $type = null,
    ) {
    }

    public static function fromRequest(array $array): self
    {
        return new self(
            name: $array['name'] ?? null,
            type: $array['type'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'type' => $this->type,
        ], fn($value) => $value !== null);
    }
}
