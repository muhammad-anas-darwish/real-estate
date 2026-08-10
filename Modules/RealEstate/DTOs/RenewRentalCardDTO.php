<?php

namespace Modules\RealEstate\DTOs;

use App\Interfaces\DTOInterface;

final readonly class RenewRentalCardDTO implements DTOInterface
{
    public function __construct(
        public string $end_date,
        public ?string $terms = null,
        public ?string $notes = null,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            end_date: $data['end_date'],
            terms: $data['terms'] ?? null,
            notes: $data['notes'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'end_date' => $this->end_date,
            'terms' => $this->terms,
            'notes' => $this->notes,
        ], fn ($value) => $value !== null);
    }
}
