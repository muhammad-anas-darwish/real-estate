<?php

namespace Modules\Communication\Entities;

use Illuminate\Collection;
use Modules\Communication\Enums\DeviceTypeEnum;
use Modules\Communication\Entities\UserFcmToken;

trait HasFcmTokens
{
    abstract public function fcmTokens(): \Illuminate\Database\Eloquent\Relations\HasMany;

    public function routeNotificationForFcm(): Collection
    {
        return $this->fcmTokens()
            ->active()
            ->pluck('token');
    }

    public function routeNotificationForFcmByDevice(?DeviceTypeEnum $deviceType = null): Collection
    {
        $query = $this->fcmTokens()->active();

        if ($deviceType) {
            $query->where('device_type', $deviceType);
        }

        return $query->pluck('token');
    }

    public function routeNotificationForPusher(): string
    {
        return 'user_' . $this->id;
    }

    public function hasFcmToken(string $token): bool
    {
        return $this->fcmTokens()->where('token', $token)->exists();
    }

    public function hasActiveFcmTokens(): bool
    {
        return $this->fcmTokens()->active()->exists();
    }

    public function fcmTokenCount(): int
    {
        return $this->fcmTokens()->count();
    }
}