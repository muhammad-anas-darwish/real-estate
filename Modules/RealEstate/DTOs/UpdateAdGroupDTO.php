<?php

namespace Modules\RealEstate\DTOs;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final readonly class UpdateAdGroupDTO
{
    public function __construct(
        public ?string $name = null,
        public ?string $description = null,
        public ?string $status = null,
        public ?bool $isArchived = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $validator = Validator::make($request->all(), [
            'name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'status' => ['nullable', 'string', 'in:active,inactive'],
            'is_archived' => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $validated = $validator->validated();

        return new self(
            name: $validated['name'] ?? null,
            description: $validated['description'] ?? null,
            status: $validated['status'] ?? null,
            isArchived: isset($validated['is_archived']) ? (bool) $validated['is_archived'] : null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
            'is_archived' => $this->isArchived,
        ], fn ($value) => $value !== null);
    }
}
