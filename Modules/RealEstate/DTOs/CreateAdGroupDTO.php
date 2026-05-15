<?php

namespace Modules\RealEstate\DTOs;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final readonly class CreateAdGroupDTO
{
    public function __construct(
        public ?string $name = null,
        public ?string $description = null,
        public ?string $status = 'active',
    ) {}

    public static function fromRequest(Request $request): self
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'status' => ['nullable', 'string', 'in:active,inactive'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $validated = $validator->validated();

        return new self(
            name: $validated['name'],
            description: $validated['description'] ?? null,
            status: $validated['status'] ?? 'active',
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
        ], fn ($value) => $value !== null);
    }
}
