<?php

namespace Modules\Communication\Services\Fcm;

enum FcmPriority: string
{
    case HIGH = 'high';
    case NORMAL = 'normal';

    public function firebaseValue(): string
    {
        return match ($this) {
            self::HIGH => 'high',
            self::NORMAL => 'normal',
        };
    }
}