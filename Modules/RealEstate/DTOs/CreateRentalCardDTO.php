<?php

namespace Modules\RealEstate\DTOs;

use App\Interfaces\DTOInterface;
use Illuminate\Http\UploadedFile;

final readonly class CreateRentalCardDTO implements DTOInterface
{
    /**
     * @param  UploadedFile[]  $pre_rental_photos
     */
    public function __construct(
        public int $property_id,
        public int $owner_id,
        public ?int $tenant_user_id,
        public ?string $external_tenant_name,
        public ?string $external_tenant_phone,
        public ?string $external_tenant_email,
        public ?string $external_tenant_id_notes,
        public string $start_date,
        public string $end_date,
        public ?string $terms,
        public ?string $notes,
        public bool $is_renewable = false,
        public array $pre_rental_photos = [],
    ) {}

    public static function fromRequest(array $data): self
    {
        $photos = $data['pre_rental_photos'] ?? [];

        if ($photos instanceof UploadedFile) {
            $photos = [$photos];
        } elseif (! is_array($photos)) {
            $photos = [];
        }

        $normalized = [];
        foreach ($photos as $photo) {
            if ($photo instanceof UploadedFile) {
                $normalized[] = $photo;
            }
        }

        return new self(
            property_id: (int) $data['property_id'],
            owner_id: (int) $data['owner_id'],
            tenant_user_id: isset($data['tenant_user_id']) ? (int) $data['tenant_user_id'] : null,
            external_tenant_name: $data['external_tenant_name'] ?? null,
            external_tenant_phone: $data['external_tenant_phone'] ?? null,
            external_tenant_email: $data['external_tenant_email'] ?? null,
            external_tenant_id_notes: $data['external_tenant_id_notes'] ?? null,
            start_date: $data['start_date'],
            end_date: $data['end_date'],
            terms: $data['terms'] ?? null,
            notes: $data['notes'] ?? null,
            is_renewable: (bool) ($data['is_renewable'] ?? false),
            pre_rental_photos: $normalized,
        );
    }

    public function toArray(): array
    {
        return [
            'property_id' => $this->property_id,
            'owner_id' => $this->owner_id,
            'tenant_user_id' => $this->tenant_user_id,
            'external_tenant_name' => $this->external_tenant_name,
            'external_tenant_phone' => $this->external_tenant_phone,
            'external_tenant_email' => $this->external_tenant_email,
            'external_tenant_id_notes' => $this->external_tenant_id_notes,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'terms' => $this->terms,
            'notes' => $this->notes,
            'is_renewable' => $this->is_renewable,
        ];
    }
}
