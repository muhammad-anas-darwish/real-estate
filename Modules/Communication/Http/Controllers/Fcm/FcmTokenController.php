<?php

namespace Modules\Communication\Http\Controllers\Fcm;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Communication\Http\Requests\RegisterFcmTokenRequest;
use Modules\Communication\Services\Fcm\FcmTokenService;

class FcmTokenController extends Controller
{
    public function __construct(
        protected readonly FcmTokenService $tokenService
    ) {}

    public function store(RegisterFcmTokenRequest $request)
    {
        $user = Auth::user();

        $this->tokenService->registerToken(
            $user,
            $request->validated('token'),
            $request->validated('device_type')
        );

        return $this->successResponse([], 'FCM token registered');
    }

    public function destroy(RegisterFcmTokenRequest $request)
    {
        $user = Auth::user();

        if ($request->has('token')) {
            $this->tokenService->revokeToken($request->validated('token'));
        } else {
            $this->tokenService->revokeAllForUser($user);
        }

        return $this->successResponse([], 'FCM token revoked');
    }
}