<?php

namespace Modules\Deposit\DTOs;

use App\Interfaces\DTOInterface;

final readonly class CreateDepositDTO implements DTOInterface
{
    public function __construct(
        public int $property_id,
        public int $buyer_id,
        public int $seller_id,
        public float $amount,
        public string $currency,
        public ?string $terms,
        public ?string $notes,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            property_id: (int) $data['property_id'],
            buyer_id: (int) ($data['buyer_id'] ?? auth()->id()),
            seller_id: (int) $data['seller_id'],
            amount: (float) $data['amount'],
            currency: $data['currency'] ?? 'SAR',
            terms: $data['terms'] ?? null,
            notes: $data['notes'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'property_id' => $this->property_id,
            'buyer_id' => $this->buyer_id,
            'seller_id' => $this->seller_id,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'terms' => $this->terms,
            'notes' => $this->notes,
        ];
    }
}
