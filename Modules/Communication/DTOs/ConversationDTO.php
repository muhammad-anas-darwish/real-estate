<?php

namespace Modules\Communication\DTOs;

use App\Interfaces\DTOInterface;

readonly final class ConversationDTO implements DTOInterface
{
    public function __construct(
        public ?int $property_id = null,
        public ?string $type = null,
        public ?int $initiator_id = null,
        public ?int $recipient_id = null,
    ) {
    }

    public static function fromRequest(array $array): self
    {
        return new self(
            property_id: $array['property_id'] ?? null,
            type: $array['type'] ?? null,
            initiator_id: $array['initiator_id'] ?? null,
            recipient_id: $array['recipient_id'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'property_id' => $this->property_id,
            'type' => $this->type,
            'initiator_id' => $this->initiator_id,
            'recipient_id' => $this->recipient_id,
        ], fn($value) => $value !== null);
    }
}