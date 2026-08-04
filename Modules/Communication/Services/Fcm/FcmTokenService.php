<?php

namespace Modules\Communication\Services\Fcm;

use Modules\Auth\Entities\User;
use Modules\Communication\Entities\UserFcmToken;
use Modules\Communication\Enums\DeviceTypeEnum;

class FcmTokenService
{
    public function registerToken(User $user, string $token, string $deviceType): UserFcmToken
    {
        return UserFcmToken::updateOrCreate(
            ['token' => $token],
            [
                'user_id' => $user->id,
                'device_type' => DeviceTypeEnum::tryFrom($deviceType) ?? DeviceTypeEnum::ANDROID,
                'last_used_at' => now(),
            ]
        );
    }

    public function revokeToken(string $token, ?string $deviceType = null): bool
    {
        $query = UserFcmToken::where('token', $token);

        if ($deviceType) {
            $query->where('device_type', DeviceTypeEnum::tryFrom($deviceType));
        }

        return $query->delete();
    }

    public function revokeAllForUserByDeviceType(User $user, string $deviceType): int
    {
        return UserFcmToken::where('user_id', $user->id)
            ->where('device_type', DeviceTypeEnum::tryFrom($deviceType))
            ->delete();
    }

    public function revokeAllForUser(User $user): int
    {
        return UserFcmToken::where('user_id', $user->id)->delete();
    }

    public function refreshToken(string $oldToken, string $newToken): ?UserFcmToken
    {
        $existing = UserFcmToken::where('token', $oldToken)->first();

        if (! $existing) {
            return null;
        }

        $userId = $existing->user_id;
        $deviceType = $existing->device_type;

        $existing->delete();

        return UserFcmToken::updateOrCreate(
            ['token' => $newToken],
            [
                'user_id' => $userId,
                'device_type' => $deviceType,
                'last_used_at' => now(),
            ]
        );
    }

    public function removeInvalidToken(string $token): void
    {
        UserFcmToken::where('token', $token)->delete();
    }

    public function removeOldTokens(int $days = 90): int
    {
        return UserFcmToken::where('last_used_at', '<', now()->subDays($days))->delete();
    }

    public function getActiveTokensForUser(int $userId): array
    {
        return UserFcmToken::forUser($userId)
            ->active()
            ->pluck('token')
            ->toArray();
    }
}
