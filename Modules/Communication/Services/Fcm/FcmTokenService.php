<?php

namespace Modules\Communication\Services\Fcm;

use Illuminate\Support\Facades\DB;
use Modules\Auth\Entities\User;
use Modules\Communication\Entities\UserFcmToken;
use Modules\Communication\Enums\DeviceTypeEnum;

class FcmTokenService
{
    public function store(int $userId, string $token, string $deviceType): UserFcmToken
    {
        $existing = UserFcmToken::where('token', $token)->first();

        if ($existing) {
            if ($existing->user_id !== $userId) {
                $existing->update(['user_id' => $userId, 'last_used_at' => now()]);
            }

            return $existing;
        }

        return UserFcmToken::updateOrCreate(
            ['token' => $token],
            [
                'user_id' => $userId,
                'device_type' => DeviceTypeEnum::tryFrom($deviceType) ?? DeviceTypeEnum::ANDROID,
                'last_used_at' => now(),
            ]
        );
    }

    public function removeInvalidToken(string $token): void
    {
        UserFcmToken::where('token', $token)->delete();
    }

    public function removeAllForUser(int $userId): int
    {
        return UserFcmToken::where('user_id', $userId)->delete();
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