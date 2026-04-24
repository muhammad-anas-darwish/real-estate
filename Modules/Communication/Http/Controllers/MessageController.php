<?php

namespace Modules\Communication\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Communication\Http\Resources\MessageResource;
use Modules\Communication\Services\ChatService;
use Modules\Communication\Services\MessageService;

class MessageController extends Controller
{
    public function __construct(
        protected readonly ChatService $chatService,
        protected readonly MessageService $messageService
    ) {}

    public function index(int $roomId)
    {
        $messages = $this->messageService->getRoomMessages(
            $roomId,
            Auth::user(),
            20
        );

        return $this->paginatedResponse(
            MessageResource::collection($messages)
        );
    }

    public function store(StoreMessageRequest $request, int $roomId)
    {
        $data = $request->validated();

        $message = $this->chatService->sendMessage(
            Auth::user(),
            $roomId,
            $data
        );

        return $this->successResponse(
            MessageResource::make($message)
        )->created('message');
    }

    public function destroy(int $roomId, int $messageId)
    {
        $this->chatService->deleteMessage(
            Auth::user(),
            $messageId
        );

        return $this->successResponse()->deleted('message');
    }
}