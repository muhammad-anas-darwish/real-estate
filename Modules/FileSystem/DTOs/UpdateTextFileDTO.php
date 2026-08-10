<?php

namespace Modules\FileSystem\DTOs;

use App\Interfaces\DTOInterface;

final readonly class UpdateTextFileDTO implements DTOInterface
{
    public function __construct(
        public ?string $name,
        public ?string $content,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            name: $data['name'] ?? null,
            content: $data['content'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'content' => $this->content,
        ], fn ($v) => $v !== null);
    }
}
