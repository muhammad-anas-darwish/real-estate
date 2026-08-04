<?php

namespace Modules\Ledger\DTOs;

use App\Interfaces\DTOInterface;

final readonly class AccountDTO implements DTOInterface
{
    public function __construct(
        public ?string $code = null,
        public ?string $name = null,
        public ?string $accountNumber = null,
        public ?string $accountCategory = null,
        public ?string $description = null,
        public ?string $currency = null,
        public ?int $parentId = null,
        public ?int $sortOrder = null,
        public ?bool $isActive = null,
    ) {}

    public static function fromRequest(array $array): self
    {
        return new self(
            code: $array['code'] ?? null,
            name: $array['name'] ?? null,
            accountNumber: $array['account_number'] ?? null,
            accountCategory: $array['account_category'] ?? null,
            description: $array['description'] ?? null,
            currency: $array['currency'] ?? null,
            parentId: $array['parent_id'] ?? null,
            sortOrder: $array['sort_order'] ?? null,
            isActive: $array['is_active'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'code' => $this->code,
            'name' => $this->name,
            'account_number' => $this->accountNumber,
            'account_category' => $this->accountCategory,
            'description' => $this->description,
            'currency' => $this->currency,
            'parent_id' => $this->parentId,
            'sort_order' => $this->sortOrder,
            'is_active' => $this->isActive,
        ], fn ($value) => $value !== null);
    }
}
