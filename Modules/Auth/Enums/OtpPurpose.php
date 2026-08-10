<?php

namespace Modules\Auth\Enums;

enum OtpPurpose: string
{
    case LOGIN = 'login';

    public function label(): string
    {
        return match ($this) {
            self::LOGIN => 'Login',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
