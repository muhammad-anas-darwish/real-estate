<?php

namespace Modules\Auth\DTOs;

use App\Interfaces\DTOInterface;

final readonly class VerifyOtpDTO implements DTOInterface
{
    public function __construct(
        public int $user_id,
        public string $code,
    ) {}

    public static function fromRequest(array $array): self
    {
        return new self(
            user_id: (int) ($array['user_id'] ?? 0),
            code: $array['code'] ?? '',
        );
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->user_id,
            'code' => $this->code,
        ];
    }
}
