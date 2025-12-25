<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Traits\ApiResponses;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;
use Laravel\Fortify\Contracts\LoginResponse;
use Laravel\Fortify\Contracts\LogoutResponse;
use Laravel\Fortify\Contracts\RegisterResponse;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->instance(LogoutResponse::class, new class implements LogoutResponse {
            use ApiResponses;

            public function toResponse($request)
            {
                $request->user()->tokens()->delete();
                return $this->successResponse(message: __("auth.logout.success"));
            }
        });

        $this->app->instance(RegisterResponse::class, new class implements RegisterResponse {
            use ApiResponses;

            public function toResponse($request)
            {
                $user = $request->user('sanctum');
                return $this->successResponse([
                    'user' => $user,
                    'token' => $user->createToken($request->email)->plainTextToken,
                ], __("auth.registration.success"));
            }
        });

        $this->app->instance(LoginResponse::class, new class implements LoginResponse {
            use ApiResponses;

            public function toResponse($request)
            {
                $user = $request->user();
                return $this->successResponse([
                    'user' => $user,
                    'token' => $user->createToken($request->email ?? $user->email)->plainTextToken,
                ], __("auth.login.success"));
            }
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::redirectUserForTwoFactorAuthenticationUsing(RedirectIfTwoFactorAuthenticatable::class);

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });
    }
}
