<?php

namespace Modules\Auth\Services;

use App\Services\BaseService;
use Modules\Auth\Entities\OtpCode;
use Modules\Auth\Entities\User;
use Modules\Auth\Enums\OtpPurpose;
use Modules\Auth\Notifications\OtpNotification;

class OtpService extends BaseService
{
    public const CACHE_TAG = 'otp';

    private const CODE_LENGTH = 6;

    private const MAX_ATTEMPTS = 3;

    private const RESEND_WINDOW_SECONDS = 60;

    public function generate(User $user, OtpPurpose $purpose): OtpCode
    {
        $this->invalidatePreviousCodes($user, $purpose);
        $this->ensureNotRateLimited($user, $purpose);

        $code = $this->generateCode();

        $otp = OtpCode::create([
            'user_id' => $user->id,
            'code' => $code,
            'purpose' => $purpose,
            'expires_at' => now()->addMinutes(config('auth.otp.expiry_minutes', 5)),
        ]);

        $user->notify(new OtpNotification($code, $purpose));

        return $otp;
    }

    public function verify(int $userId, string $code, OtpPurpose $purpose): OtpCode
    {
        $otp = OtpCode::query()
            ->where('user_id', $userId)
            ->where('code', $code)
            ->where('purpose', $purpose)
            ->whereNull('verified_at')
            ->latest()
            ->first();

        if (! $otp) {
            throw new \RuntimeException(__('auth.otp.invalid_code'));
        }

        if ($otp->isExpired()) {
            throw new \RuntimeException(__('auth.otp.expired'));
        }

        $otp->markAsVerified();

        return $otp;
    }

    private function generateCode(): string
    {
        $max = (int) str_repeat('9', self::CODE_LENGTH);

        return str_pad((string) random_int(0, $max), self::CODE_LENGTH, '0', STR_PAD_LEFT);
    }

    private function invalidatePreviousCodes(User $user, OtpPurpose $purpose): void
    {
        OtpCode::query()
            ->where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->whereNull('verified_at')
            ->where('expires_at', '>', now())
            ->update(['expires_at' => now()->subSecond()]);
    }

    private function ensureNotRateLimited(User $user, OtpPurpose $purpose): void
    {
        $recentCount = OtpCode::query()
            ->where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->where('created_at', '>=', now()->subMinutes(15))
            ->count();

        if ($recentCount >= self::MAX_ATTEMPTS) {
            throw new \RuntimeException(__('auth.otp.too_many_attempts'));
        }

        $latest = OtpCode::query()
            ->where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->where('created_at', '>=', now()->subSeconds(self::RESEND_WINDOW_SECONDS))
            ->exists();

        if ($latest) {
            throw new \RuntimeException(__('auth.otp.wait_before_resend', ['seconds' => self::RESEND_WINDOW_SECONDS]));
        }
    }
}
