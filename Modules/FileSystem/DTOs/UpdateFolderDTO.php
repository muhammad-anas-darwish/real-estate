<?php

namespace Modules\FileSystem\DTOs;

use App\Interfaces\DTOInterface;

final readonly class UpdateFolderDTO implements DTOInterface
{
    public function __construct(
        public string $name,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(name: $data['name']);
    }

    public function toArray(): array
    {
        return ['name' => $this->name];
    }
}
