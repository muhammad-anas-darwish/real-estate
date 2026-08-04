<?php

namespace Modules\ServiceProvider\DTOs;

use App\Interfaces\DTOInterface;

final readonly class ServiceRequestDTO implements DTOInterface
{
    public function __construct(
        public ?int $provider_id = null,
        public ?int $property_id = null,
        public ?string $service_type = null,
        public ?string $scheduled_at = null,
        public ?string $client_notes = null,
        public ?float $price = null,
    ) {}

    public static function fromRequest(array $array): self
    {
        return new self(
            provider_id: isset($array['provider_id']) ? (int) $array['provider_id'] : null,
            property_id: isset($array['property_id']) ? (int) $array['property_id'] : null,
            service_type: $array['service_type'] ?? null,
            scheduled_at: $array['scheduled_at'] ?? null,
            client_notes: $array['client_notes'] ?? null,
            price: isset($array['price']) ? (float) $array['price'] : null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'provider_id' => $this->provider_id,
            'property_id' => $this->property_id,
            'service_type' => $this->service_type,
            'scheduled_at' => $this->scheduled_at,
            'client_notes' => $this->client_notes,
            'price' => $this->price,
        ], fn ($value) => $value !== null);
    }
}
