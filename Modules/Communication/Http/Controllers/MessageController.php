<?php

namespace Modules\Communication\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Communication\Http\Requests\StoreMessageRequest;
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

    /**
 * Send a message to a chat room.
 *
 * @bodyParam body string required Message text content
 * @bodyParam type string optional Message type: "text", "image", "file"
 * @bodyParam parent_id integer optional ID of message being replied to
 * @bodyParam attachment file optional File attachment (max 20MB)
 *
 * @response 201 {"success": true, "message": "message created", "data": {...}}
 */
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
