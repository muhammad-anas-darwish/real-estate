<?php

namespace Modules\Subscription\DTOs;

use App\Interfaces\DTOInterface;

final readonly class SubscriptionPlanFeatureDTO implements DTOInterface
{
    public function __construct(
        public ?int $plan_id = null,
        public ?int $feature_id = null,
        public ?bool $is_enabled = null,
        public ?int $limit_value = null,
    ) {}

    public static function fromRequest(array $array): self
    {
        return new self(
            plan_id: $array['plan_id'] ?? null,
            feature_id: $array['feature_id'] ?? null,
            is_enabled: $array['is_enabled'] ?? null,
            limit_value: $array['limit_value'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'plan_id' => $this->plan_id,
            'feature_id' => $this->feature_id,
            'is_enabled' => $this->is_enabled,
            'limit_value' => $this->limit_value,
        ], fn ($value) => $value !== null);
    }
}
