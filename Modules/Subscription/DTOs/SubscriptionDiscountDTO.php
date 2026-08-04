<?php

namespace Modules\Subscription\DTOs;

use App\Interfaces\DTOInterface;

final readonly class SubscriptionDiscountDTO implements DTOInterface
{
    public function __construct(
        public ?string $code = null,
        public ?string $type = null,
        public ?float $value = null,
        public ?int $plan_id = null,
        public ?int $max_uses = null,
        public ?string $expires_at = null,
        public ?bool $is_active = null,
    ) {}

    public static function fromRequest(array $array): self
    {
        return new self(
            code: $array['code'] ?? null,
            type: $array['type'] ?? null,
            value: $array['value'] ?? null,
            plan_id: $array['plan_id'] ?? null,
            max_uses: $array['max_uses'] ?? null,
            expires_at: $array['expires_at'] ?? null,
            is_active: $array['is_active'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'code' => $this->code,
            'type' => $this->type,
            'value' => $this->value,
            'plan_id' => $this->plan_id,
            'max_uses' => $this->max_uses,
            'expires_at' => $this->expires_at,
            'is_active' => $this->is_active,
        ], fn ($value) => $value !== null);
    }
}
