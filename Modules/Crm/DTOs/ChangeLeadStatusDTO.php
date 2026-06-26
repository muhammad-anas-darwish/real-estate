<?php

namespace Modules\Crm\DTOs;

use App\Interfaces\DTOInterface;

readonly final class ChangeLeadStatusDTO implements DTOInterface
{
    public function __construct(
        public string $status,
        public ?string $lost_reason = null,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            status: $data['status'],
            lost_reason: $data['lost_reason'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'lost_reason' => $this->lost_reason,
        ];
    }
}
