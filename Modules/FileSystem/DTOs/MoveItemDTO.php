<?php

namespace Modules\FileSystem\DTOs;

use App\Interfaces\DTOInterface;

final readonly class MoveItemDTO implements DTOInterface
{
    public function __construct(
        public int $target_folder_id,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(target_folder_id: (int) $data['target_folder_id']);
    }

    public function toArray(): array
    {
        return ['target_folder_id' => $this->target_folder_id];
    }
}
