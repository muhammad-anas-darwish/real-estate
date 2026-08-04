<?php

namespace Modules\Subscription\DTOs;

use App\Interfaces\DTOInterface;

final readonly class SubscriptionStatusLogDTO implements DTOInterface
{
    public function __construct(
        public ?int $subscription_id = null,
        public ?string $from_status = null,
        public ?string $to_status = null,
        public ?string $reason = null,
    ) {}

    public static function fromRequest(array $array): self
    {
        return new self(
            subscription_id: $array['subscription_id'] ?? null,
            from_status: $array['from_status'] ?? null,
            to_status: $array['to_status'] ?? null,
            reason: $array['reason'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'subscription_id' => $this->subscription_id,
            'from_status' => $this->from_status,
            'to_status' => $this->to_status,
            'reason' => $this->reason,
        ], fn ($value) => $value !== null);
    }
}
