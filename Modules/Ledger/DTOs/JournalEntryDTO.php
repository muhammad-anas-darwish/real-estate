<?php

namespace Modules\Ledger\DTOs;

use App\Interfaces\DTOInterface;

final readonly class JournalEntryDTO implements DTOInterface
{
    public function __construct(
        public string $entryDate,
        public array $lines,
        public ?string $description = null,
        public ?string $reference = null,
        public ?string $notes = null,
    ) {}

    public static function fromRequest(array $array): self
    {
        return new self(
            entryDate: $array['entry_date'],
            lines: $array['lines'] ?? [],
            description: $array['description'] ?? null,
            reference: $array['reference'] ?? null,
            notes: $array['notes'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'entry_date' => $this->entryDate,
            'description' => $this->description,
            'reference' => $this->reference,
            'notes' => $this->notes,
        ], fn ($value) => $value !== null);
    }
}
