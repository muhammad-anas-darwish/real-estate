<?php

namespace Modules\Subscription\DTOs;

use App\Interfaces\DTOInterface;

final readonly class SubscriptionDTO implements DTOInterface
{
    public function __construct(
        public ?int $user_id = null,
        public ?int $plan_id = null,
        public ?int $discount_id = null,
        public ?string $status = null,
        public ?string $starts_at = null,
        public ?string $ends_at = null,
        public ?string $cancelled_at = null,
    ) {}

    public static function fromRequest(array $array): self
    {
        return new self(
            user_id: $array['user_id'] ?? null,
            plan_id: $array['plan_id'] ?? null,
            discount_id: $array['discount_id'] ?? null,
            status: $array['status'] ?? null,
            starts_at: $array['starts_at'] ?? null,
            ends_at: $array['ends_at'] ?? null,
            cancelled_at: $array['cancelled_at'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'user_id' => $this->user_id,
            'plan_id' => $this->plan_id,
            'discount_id' => $this->discount_id,
            'status' => $this->status,
            'starts_at' => $this->starts_at,
            'ends_at' => $this->ends_at,
            'cancelled_at' => $this->cancelled_at,
        ], fn ($value) => $value !== null);
    }
}
