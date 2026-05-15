<?php

namespace Modules\Core\SubModules\Location\DTOs;

use App\Interfaces\DTOInterface;

final readonly class CityDTO implements DTOInterface
{
    public function __construct(
        public ?string $name = null,
        public ?int $country_id = null,
        public ?string $state_province = null,
        public ?string $postal_code = null,
        public ?bool $is_active = null,
    ) {}

    public static function fromRequest(array $array): self
    {
        return new self(
            name: $array['name'] ?? null,
            country_id: $array['country_id'] ?? null,
            state_province: $array['state_province'] ?? null,
            postal_code: $array['postal_code'] ?? null,
            is_active: $array['is_active'] ?? null,
        );
    }
}
