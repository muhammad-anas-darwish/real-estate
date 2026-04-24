<?php

namespace Modules\Communication\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Communication\Events\UserTypingEvent;
use Modules\Communication\Services\ChatService;

class TypingController extends Controller
{
    public function __construct(
        protected readonly ChatService $chatService
    ) {}

    public function store(int $roomId)
    {
        $this->chatService->authorizeRoom($roomId, Auth::user());

        event(new UserTypingEvent(Auth::user(), $roomId));

        return $this->successResponse([], 'typing event broadcasted');
    }
}