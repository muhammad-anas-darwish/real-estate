<?php

namespace Modules\Communication\Entities;

use Illuminate\Notifications\Notifiable;
use Modules\Communication\Entities\UserFcmToken;

trait HasFcmTokens
{
    public function fcmTokens(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(UserFcmToken::class);
    }

    public function routeNotificationForFcm(): array
    {
        return $this->fcmTokens()
            ->active()
            ->pluck('token')
            ->toArray();
    }

    public function routeNotificationForPusher(): string
    {
        return 'user_' . $this->id;
    }

    public function hasFcmToken(string $token): bool
    {
        return $this->fcmTokens()->where('token', $token)->exists();
    }
}