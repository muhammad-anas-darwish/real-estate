<?php

namespace Modules\RealEstate\DTOs;

use App\Interfaces\DTOInterface;
use Modules\RealEstate\Entities\AdGroup;

final readonly class AdGroupDTO implements DTOInterface
{
    public function __construct(
        public ?int $id = null,
        public ?string $name = null,
        public ?string $description = null,
        public ?string $status = null,
        public ?int $createdBy = null,
    ) {}

    public static function fromRequest(array $array): self
    {
        return new self(
            name: $array['name'] ?? null,
            description: $array['description'] ?? null,
            status: $array['status'] ?? null,
            createdBy: $array['created_by'] ?? null,
        );
    }

    public static function fromModel(AdGroup $model): self
    {
        return new self(
            id: $model->id,
            name: $model->name,
            description: $model->description,
            status: $model->status,
            createdBy: $model->created_by,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
            'created_by' => $this->createdBy,
        ], fn ($value) => $value !== null);
    }
}
