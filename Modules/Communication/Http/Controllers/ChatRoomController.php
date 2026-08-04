<?php

namespace Modules\Communication\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Communication\Http\Requests\StoreChatRoomRequest;
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

    /**
     * Store a new chat room.
     *
     * Creates a private chat between two users or a property-based chat with the property's agent.
     *
     * @bodyParam type string required Type: "private" or "property"
     * @bodyParam recipient_id integer required if type=private - User ID to chat with
     * @bodyParam property_id integer required if type=property - Property ID
     *
     * @response 201 {"success": true, "message": "chat_room created", "data": {...}}
     */
    public function store(StoreChatRoomRequest $request)
    {
        $type = $request->validated('type');

        try {
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
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
                'data' => null,
            ], 404);
        }
    }

    public function show(int $roomId)
    {
        $room = $this->chatService->getRoom($roomId, Auth::user());

        return $this->successResponse(ChatRoomResource::make($room));
    }
}
