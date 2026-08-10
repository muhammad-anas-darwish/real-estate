<?php

namespace Modules\Crm\DTOs;

use App\Interfaces\DTOInterface;

final readonly class LeadDTO implements DTOInterface
{
    public function __construct(
        public int $trader_id,
        public ?string $name,
        public ?string $phone,
        public ?string $email,
        public ?string $source,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            trader_id: $data['trader_id'] ?? auth()->id(),
            name: $data['name'] ?? null,
            phone: $data['phone'] ?? null,
            email: $data['email'] ?? null,
            source: $data['source'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'trader_id' => $this->trader_id,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'source' => $this->source,
        ], fn ($value) => $value !== null);
    }
}
