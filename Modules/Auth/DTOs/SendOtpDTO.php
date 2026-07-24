<?php

namespace Modules\Auth\DTOs;

use App\Interfaces\DTOInterface;

final readonly class SendOtpDTO implements DTOInterface
{
    public function __construct(
        public string $email,
        public string $password,
    ) {}

    public static function fromRequest(array $array): self
    {
        return new self(
            email: $array['email'] ?? '',
            password: $array['password'] ?? '',
        );
    }

    public function toArray(): array
    {
        return [
            'email' => $this->email,
            'password' => $this->password,
        ];
    }
}
