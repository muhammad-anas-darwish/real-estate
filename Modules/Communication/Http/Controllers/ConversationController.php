<?php

namespace Modules\Communication\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Communication\DTOs\ConversationDTO;
use Modules\Communication\Http\Resources\ConversationResource;
use Modules\Communication\Services\ConversationService;

class ConversationController extends Controller
{
    public function __construct(
        protected readonly ConversationService $conversationService
    ) {}

    public function index()
    {
        $conversations = $this->conversationService->all(Auth::id());

        return $this->paginatedResponse(ConversationResource::collection($conversations));
    }

    public function show($id)
    {
        $conversation = $this->conversationService->find($id, Auth::id());

        return $this->successResponse(ConversationResource::make($conversation));
    }

    public function store(ConversationDTO $dto)
    {
        $dto = ConversationDTO::fromRequest(array_merge($dto->toArray(), [
            'initiator_id' => Auth::id(),
        ]));

        $conversation = $this->conversationService->store($dto);

        return $this->successResponse(ConversationResource::make($conversation))->created('conversation');
    }

    public function getOrCreateForProperty($propertyId)
    {
        $conversation = $this->conversationService->getOrCreateForProperty($propertyId, Auth::id());

        return $this->successResponse(ConversationResource::make($conversation));
    }

    public function destroy($id)
    {
        $this->conversationService->destroy($id, Auth::id());

        return $this->successResponse()->deleted('conversation');
    }
}
