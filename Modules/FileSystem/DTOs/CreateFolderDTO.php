<?php

namespace Modules\FileSystem\DTOs;

use App\Interfaces\DTOInterface;

final readonly class CreateFolderDTO implements DTOInterface
{
    public function __construct(
        public int $user_id,
        public ?int $parent_id,
        public string $name,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            user_id: (int) $data['user_id'],
            parent_id: isset($data['parent_id']) ? (int) $data['parent_id'] : null,
            name: $data['name'],
        );
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->user_id,
            'parent_id' => $this->parent_id,
            'name' => $this->name,
        ];
    }
}
