<?php

namespace Modules\Deposit\DTOs;

use App\Interfaces\DTOInterface;

final readonly class UpdateDepositDTO implements DTOInterface
{
    public function __construct(
        public ?float $amount,
        public ?string $terms,
        public ?string $notes,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            amount: isset($data['amount']) ? (float) $data['amount'] : null,
            terms: $data['terms'] ?? null,
            notes: $data['notes'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'amount' => $this->amount,
            'terms' => $this->terms,
            'notes' => $this->notes,
        ], fn ($value) => $value !== null);
    }
}
