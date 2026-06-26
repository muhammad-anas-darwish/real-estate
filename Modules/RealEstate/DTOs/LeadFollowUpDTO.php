<?php

namespace Modules\RealEstate\DTOs;

use App\Interfaces\DTOInterface;

final readonly class LeadFollowUpDTO implements DTOInterface
{
    public function __construct(
        public int $followable_id,
        public string $followable_type,
        public int $agent_id,
        public string $scheduled_at,
        public ?string $contact_method = null,
        public ?int $duration_minutes = 30,
        public ?int $buffer_minutes = 15,
        public ?string $notes = null,
        public ?string $contact_name = null,
        public ?string $contact_phone = null,
    ) {}

    public static function fromRequest(array $array): self
    {
        return new self(
            followable_id: (int) $array['followable_id'],
            followable_type: $array['followable_type'],
            agent_id: (int) $array['agent_id'],
            scheduled_at: $array['scheduled_at'],
            contact_method: $array['contact_method'] ?? null,
            duration_minutes: $array['duration_minutes'] ?? 30,
            buffer_minutes: $array['buffer_minutes'] ?? 15,
            notes: $array['notes'] ?? null,
            contact_name: $array['contact_name'] ?? null,
            contact_phone: $array['contact_phone'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'followable_id' => $this->followable_id,
            'followable_type' => $this->followable_type,
            'agent_id' => $this->agent_id,
            'scheduled_at' => $this->scheduled_at,
            'contact_method' => $this->contact_method,
            'duration_minutes' => $this->duration_minutes,
            'buffer_minutes' => $this->buffer_minutes,
            'notes' => $this->notes,
            'contact_name' => $this->contact_name,
            'contact_phone' => $this->contact_phone,
        ], fn ($value) => $value !== null);
    }
}
