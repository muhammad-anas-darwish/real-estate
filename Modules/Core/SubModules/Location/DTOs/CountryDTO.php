<?php

namespace Modules\Core\SubModules\Location\DTOs;

use App\Interfaces\DTOInterface;

final readonly class CountryDTO implements DTOInterface
{
    public function __construct(
        public ?string $name = null,
        public ?string $code = null,
        public ?string $phone_code = null,
        public ?bool $is_active = null,
    ) {}

    public static function fromRequest(array $array): self
    {
        return new self(
            name: $array['name'],
            code: $array['code'],
            phone_code: $array['phone_code'],
            is_active: $array['is_active'],
        );
    }
}
