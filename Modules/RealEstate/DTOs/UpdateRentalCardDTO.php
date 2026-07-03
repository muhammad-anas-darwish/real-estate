<?php

namespace Modules\RealEstate\DTOs;

use App\Interfaces\DTOInterface;

final readonly class UpdateRentalCardDTO implements DTOInterface
{
    public function __construct(
        public ?string $end_date = null,
        public ?string $terms = null,
        public ?string $notes = null,
        public ?bool $is_renewable = null,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            end_date: $data['end_date'] ?? null,
            terms: $data['terms'] ?? null,
            notes: $data['notes'] ?? null,
            is_renewable: isset($data['is_renewable']) ? (bool) $data['is_renewable'] : null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'end_date' => $this->end_date,
            'terms' => $this->terms,
            'notes' => $this->notes,
            'is_renewable' => $this->is_renewable,
        ], fn ($value) => $value !== null);
    }
}
