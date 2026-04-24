<?php

namespace Modules\Communication\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Communication\DTOs\MessageDTO;
use Modules\Communication\Http\Resources\MessageResource;
use Modules\Communication\Services\MessageService;

class MessageController extends Controller
{
    public function __construct(
        protected readonly MessageService $messageService
    ) {
    }

    public function index($conversationId)
    {
        $messages = $this->messageService->all($conversationId);

        return $this->paginatedResponse(MessageResource::collection($messages));
    }

    public function show($id)
    {
        $message = $this->messageService->find($id);

        return $this->successResponse(MessageResource::make($message));
    }

    public function store(MessageDTO $dto)
    {
        $dto = MessageDTO::fromRequest(array_merge($dto->toArray(), [
            'sender_id' => Auth::id(),
        ]));

        $message = $this->messageService->store($dto);

        return $this->successResponse(MessageResource::make($message))->created('message');
    }

    public function markAsRead($conversationId)
    {
        $this->messageService->markAsRead($conversationId, Auth::id());

        return $this->successResponse([], 'Messages marked as read');
    }

    public function destroy($id)
    {
        $this->messageService->destroy($id);

        return $this->successResponse()->deleted('message');
    }
}