<?php

namespace Modules\Auth\Providers;

use App\Traits\ApiResponses;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\LoginResponse;
use Laravel\Fortify\Contracts\LogoutResponse;
use Laravel\Fortify\Contracts\PasswordResetResponse;
use Laravel\Fortify\Contracts\PasswordUpdateResponse;
use Laravel\Fortify\Contracts\ProfileInformationUpdatedResponse;
use Laravel\Fortify\Contracts\RegisterResponse;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse;
use Laravel\Fortify\Contracts\VerifyEmailResponse;
use Laravel\Fortify\Fortify;
use Modules\Auth\Actions\CreateNewUser;
use Modules\Auth\Actions\ResetUserPassword;
use Modules\Auth\Actions\UpdateUserPassword;
use Modules\Auth\Actions\UpdateUserProfileInformation;

class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // تعطيل routes الافتراضية لأننا سنستخدم custom routes
        Fortify::ignoreRoutes();

        // Logout response
        $this->app->instance(LogoutResponse::class, new class implements LogoutResponse
        {
            use ApiResponses;

            public function toResponse($request)
            {
                $request->user()->tokens()->delete();

                return $this->successResponse(message: __('auth.logout.success'));
            }
        });

        // Register response
        $this->app->instance(RegisterResponse::class, new class implements RegisterResponse
        {
            use ApiResponses;

            public function toResponse($request)
            {
                $user = $request->user();

                return $this->successResponse([
                    'user' => $user,
                    'token' => $user->createToken($request->email)->plainTextToken,
                ], __('auth.registration.success'));
            }
        });

        // Login response
        $this->app->instance(LoginResponse::class, new class implements LoginResponse
        {
            use ApiResponses;

            public function toResponse($request)
            {
                $user = $request->user();

                return $this->successResponse([
                    'user' => $user,
                    'token' => $user->createToken($request->email ?? $user->email)->plainTextToken,
                ], __('auth.login.success'));
            }
        });

        // Two Factor Login response
        $this->app->instance(TwoFactorLoginResponse::class, new class implements TwoFactorLoginResponse
        {
            use ApiResponses;

            public function toResponse($request)
            {
                $user = $request->user();

                return $this->successResponse([
                    'user' => $user,
                    'token' => $user->createToken($request->email ?? $user->email)->plainTextToken,
                ], __('Two factor authentication successful'));
            }
        });

        // Password Update response
        $this->app->instance(PasswordUpdateResponse::class, new class implements PasswordUpdateResponse
        {
            use ApiResponses;

            public function toResponse($request)
            {
                return $this->successResponse(message: __('Password updated successfully'));
            }
        });

        // Profile Information Updated response
        $this->app->instance(ProfileInformationUpdatedResponse::class, new class implements ProfileInformationUpdatedResponse
        {
            use ApiResponses;

            public function toResponse($request)
            {
                return $this->successResponse([
                    'user' => $request->user(),
                ], __('Profile updated successfully'));
            }
        });

        // Password Reset response
        $this->app->instance(PasswordResetResponse::class, new class implements PasswordResetResponse
        {
            use ApiResponses;

            public function toResponse($request)
            {
                return $this->successResponse(message: __('Password reset successfully'));
            }
        });

        // Email Verification response
        $this->app->instance(VerifyEmailResponse::class, new class implements VerifyEmailResponse
        {
            use ApiResponses;

            public function toResponse($request)
            {
                return $this->successResponse(message: __('Email verified successfully'));
            }
        });
    }

    public function boot(): void
    {
        // تحميل الـ routes المخصصة
        $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });
    }
}
