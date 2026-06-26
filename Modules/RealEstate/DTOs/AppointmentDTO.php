<?php

namespace Modules\RealEstate\DTOs;

use App\Interfaces\DTOInterface;

final readonly class AppointmentDTO implements DTOInterface
{
    public function __construct(
        public ?string $type = 'viewing',
        public ?int $property_id = null,
        public ?int $user_id = null,
        public ?int $agent_id = null,
        public ?string $scheduled_at = null,
        public ?int $duration_minutes = 30,
        public ?int $buffer_minutes = 15,
        public ?string $status = null,
        public ?string $viewing_type = 'in_person',
        public ?string $contact_method = null,
        public ?string $contact_name = null,
        public ?string $contact_phone = null,
        public ?string $notes = null,
        public ?string $agent_notes = null,
        public ?int $cancelled_by = null,
        public ?string $cancellation_reason = null,
        public ?int $max_attendees = null,
        public ?string $confirmed_at = null,
        public ?string $completed_at = null,
        public ?string $cancelled_at = null,
        public ?int $followable_id = null,
        public ?string $followable_type = null,
    ) {}

    public static function fromRequest(array $array): self
    {
        return new self(
            type: $array['type'] ?? 'viewing',
            property_id: $array['property_id'] ?? null,
            user_id: $array['user_id'] ?? null,
            agent_id: $array['agent_id'] ?? null,
            scheduled_at: $array['scheduled_at'] ?? null,
            duration_minutes: $array['duration_minutes'] ?? 30,
            buffer_minutes: $array['buffer_minutes'] ?? 15,
            status: $array['status'] ?? null,
            viewing_type: $array['viewing_type'] ?? 'in_person',
            contact_method: $array['contact_method'] ?? null,
            contact_name: $array['contact_name'] ?? null,
            contact_phone: $array['contact_phone'] ?? null,
            notes: $array['notes'] ?? null,
            agent_notes: $array['agent_notes'] ?? null,
            cancelled_by: $array['cancelled_by'] ?? null,
            cancellation_reason: $array['cancellation_reason'] ?? null,
            max_attendees: $array['max_attendees'] ?? null,
            confirmed_at: $array['confirmed_at'] ?? null,
            completed_at: $array['completed_at'] ?? null,
            cancelled_at: $array['cancelled_at'] ?? null,
            followable_id: $array['followable_id'] ?? null,
            followable_type: $array['followable_type'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'type' => $this->type,
            'property_id' => $this->property_id,
            'user_id' => $this->user_id,
            'agent_id' => $this->agent_id,
            'scheduled_at' => $this->scheduled_at,
            'duration_minutes' => $this->duration_minutes,
            'buffer_minutes' => $this->buffer_minutes,
            'status' => $this->status,
            'viewing_type' => $this->viewing_type,
            'contact_method' => $this->contact_method,
            'contact_name' => $this->contact_name,
            'contact_phone' => $this->contact_phone,
            'notes' => $this->notes,
            'agent_notes' => $this->agent_notes,
            'cancelled_by' => $this->cancelled_by,
            'cancellation_reason' => $this->cancellation_reason,
            'max_attendees' => $this->max_attendees,
            'confirmed_at' => $this->confirmed_at,
            'completed_at' => $this->completed_at,
            'cancelled_at' => $this->cancelled_at,
            'followable_id' => $this->followable_id,
            'followable_type' => $this->followable_type,
        ], fn ($value) => $value !== null);
    }
}
