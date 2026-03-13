<?php

namespace Modules\Core\SubModules\Location\DTOs;

use App\Interfaces\DTOInterface;

readonly final class CityDTO implements DTOInterface
{
    public function __construct(
        public ?string $name = null,
        public ?int $country_id = null,
        public ?string $state_province = null,
        public ?string $postal_code = null,
        public ?bool $is_active = null,
    ) {
    }

    public static function fromRequest(array $array): self
    {
        return new self(
            name: $array['name'],
            country_id: $array['country_id'],
            state_province: $array['state_province'],
            postal_code: $array['postal_code'],
            is_active: $array['is_active'],
        );
    }
}
