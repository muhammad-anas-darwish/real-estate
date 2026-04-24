<?php

namespace Modules\Communication\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Communication\Http\Resources\ChatRoomResource;
use Modules\Communication\Services\ChatService;

class ChatRoomController extends Controller
{
    public function __construct(
        protected readonly ChatService $chatService
    ) {}

    public function index()
    {
        $rooms = $this->chatService->getRoomsForUser(Auth::user());

        return $this->paginatedResponse(
            ChatRoomResource::collection($rooms)
        );
    }

    public function store(StoreChatRoomRequest $request)
    {
        $type = $request->validated('type');

        if ($type === 'property' && $request->has('property_id')) {
            $property = \Modules\RealEstate\Entities\Property::findOrFail(
                $request->validated('property_id')
            );

            $room = $this->chatService->startPropertyChat(
                Auth::user(),
                $property
            );
        } else {
            $recipient = \Modules\Auth\Entities\User::findOrFail(
                $request->validated('recipient_id')
            );

            $room = $this->chatService->startPrivateChat(
                Auth::user(),
                $recipient
            );
        }

        return $this->successResponse(
            ChatRoomResource::make($room)
        )->created('chat_room');
    }

    public function show(int $roomId)
    {
        $room = $this->chatService->getRoom($roomId, Auth::user());

        return $this->successResponse(ChatRoomResource::make($room));
    }
}