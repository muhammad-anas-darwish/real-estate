<?php

namespace Modules\RealEstate\DTOs;

use App\Interfaces\DTOInterface;

final readonly class EndRentalCardDTO implements DTOInterface
{
    public function __construct(
        public ?string $end_reason = null,
        public ?string $ended_at = null,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            end_reason: $data['end_reason'] ?? null,
            ended_at: $data['ended_at'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'end_reason' => $this->end_reason,
            'ended_at' => $this->ended_at,
        ], fn ($value) => $value !== null);
    }
}
