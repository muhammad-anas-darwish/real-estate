<?php

namespace Modules\Communication\Services;

use App\Services\BaseService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Communication\DTOs\ConversationDTO;
use Modules\Communication\Entities\Conversation;
use Modules\RealEstate\Entities\Property;

class ConversationService extends BaseService
{
    public const CACHE_TAG = 'conversations';

    public function all(int $userId): LengthAwarePaginator
    {
        return Conversation::query()
            ->where('initiator_id', $userId)
            ->orWhere('recipient_id', $userId)
            ->filter()
            ->with(['initiator', 'recipient', 'property', 'messages' => fn ($query) => $query->latest()->limit(1)])
            ->orderBy('created_at', 'desc')
            ->paginate($this->getPerPage());
    }

    public function find(int $id, int $userId): Conversation
    {
        return Conversation::where(function ($query) use ($userId) {
            $query->where('initiator_id', $userId)
                ->orWhere('recipient_id', $userId);
        })
            ->with(['initiator', 'recipient', 'property', 'messages' => fn ($query) => $query->latest()])
            ->findOrFail($id);
    }

    public function getOrCreateForProperty(int $propertyId, int $participantId): Conversation
    {
        $property = Property::findOrFail($propertyId);
        $publisherId = $property->publisher_id;

        $conversation = Conversation::where('property_id', $propertyId)
            ->where(function ($query) use ($participantId, $publisherId) {
                $query->where(function ($q) use ($participantId, $publisherId) {
                    $q->where('initiator_id', $participantId)
                        ->where('recipient_id', $publisherId);
                })->orWhere(function ($q) use ($participantId, $publisherId) {
                    $q->where('initiator_id', $publisherId)
                        ->where('recipient_id', $participantId);
                });
            })
            ->first();

        if ($conversation) {
            return $conversation;
        }

        return $this->store(ConversationDTO::fromRequest([
            'property_id' => $propertyId,
            'type' => 'property_inquiry',
            'initiator_id' => $participantId,
            'recipient_id' => $publisherId,
        ]));
    }

    public function store(ConversationDTO $dto): Conversation
    {
        return DB::transaction(function () use ($dto): Conversation {
            return Conversation::create($dto->toArray());
        });
    }

    public function destroy(int $id, int $userId): void
    {
        $conversation = $this->find($id, $userId);
        $conversation->messages()->delete();
        $conversation->delete();
    }

    public function getUnreadCount(int $userId): int
    {
        return Conversation::where('recipient_id', $userId)
            ->whereHas('messages', fn ($query) => $query->where('is_read', false)->where('sender_id', '!=', $userId))
            ->count();
    }
}