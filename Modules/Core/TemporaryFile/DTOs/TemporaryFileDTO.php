<?php

namespace Modules\Core\TemporaryFile\DTOs;

use App\Interfaces\DTOInterface\DTOInterface;
use Illuminate\Http\UploadedFile;

readonly final class TemporaryFileDTO implements DTOInterface
{
    public function __construct(
        public ?int          $id = null,
        public ?string       $type = null,
        public ?array        $files = [],
        public ?UploadedFile $file = null,
        public ?string       $created_at = null,
        public ?string       $updated_at = null,
    ) {}

    public static function fromRequest(array $array): TemporaryFileDTO
    {
        $currentFiles = $array['files'] ?? [];
        if ($array['file']) {
            $currentFiles[] = $array['file'];
        }
        return new self(
            id: $array['id'] ?? null,
            type: $array['type'] ?? null,
            files: $currentFiles,
            file: $array['file'] ?? null,
            created_at: $array['created_at'] ?? null,
            updated_at: $array['updated_at'] ?? null,
        );
    }
}
