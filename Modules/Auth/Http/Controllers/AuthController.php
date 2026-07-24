<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Modules\Auth\DTOs\SendOtpDTO;
use Modules\Auth\DTOs\VerifyOtpDTO;
use Modules\Auth\Entities\User;
use Modules\Auth\Enums\OtpPurpose;
use Modules\Auth\Services\OtpService;

class AuthController extends Controller
{
    public function __construct(
        protected OtpService $otpService,
    ) {}

    public function sendOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $dto = SendOtpDTO::fromRequest($validated);

        $user = User::where('email', $dto->email)->first();

        if (! $user || ! Hash::check($dto->password, $user->password)) {
            return $this->failedResponse(__('auth.failed'), 401);
        }

        try {
            $this->otpService->generate($user, OtpPurpose::LOGIN);
        } catch (\RuntimeException $e) {
            return $this->failedResponse($e->getMessage(), 429);
        }

        return $this->successResponse(
            data: ['user_id' => $user->id],
            message: __('auth.otp.sent')
        );
    }

    public function verifyOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'code' => ['required', 'string', 'min:6', 'max:6'],
        ]);

        $dto = VerifyOtpDTO::fromRequest($validated);

        try {
            $this->otpService->verify($dto->user_id, $dto->code, OtpPurpose::LOGIN);
        } catch (\RuntimeException $e) {
            return $this->failedResponse($e->getMessage(), 422);
        }

        $user = User::findOrFail($dto->user_id);

        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        Auth::login($user);

        return $this->successResponse([
            'user' => $user,
            'token' => $user->createToken($user->email)->plainTextToken,
        ], __('auth.login.success'));
    }
}
