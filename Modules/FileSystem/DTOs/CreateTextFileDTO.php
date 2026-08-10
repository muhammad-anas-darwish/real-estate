<?php

namespace Modules\FileSystem\DTOs;

use App\Interfaces\DTOInterface;

final readonly class CreateTextFileDTO implements DTOInterface
{
    public function __construct(
        public int $user_id,
        public int $folder_id,
        public string $name,
        public string $content = '',
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            user_id: (int) $data['user_id'],
            folder_id: (int) $data['folder_id'],
            name: $data['name'],
            content: $data['content'] ?? '',
        );
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->user_id,
            'folder_id' => $this->folder_id,
            'name' => $this->name,
            'content' => $this->content,
        ];
    }
}
