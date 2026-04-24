<?php

namespace Modules\Communication\Services;

use App\Services\BaseService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Communication\DTOs\MessageDTO;
use Modules\Communication\Entities\Message;

class MessageService extends BaseService
{
    public const CACHE_TAG = 'messages';

    public function all(int $conversationId): LengthAwarePaginator
    {
        return Message::where('conversation_id', $conversationId)
            ->with(['sender'])
            ->orderBy('created_at', 'asc')
            ->paginate($this->getPerPage(50));
    }

    public function find(int $id): Message
    {
        return Message::with(['sender', 'conversation'])->findOrFail($id);
    }

    public function store(MessageDTO $dto): Message
    {
        return DB::transaction(function () use ($dto): Message {
            return Message::create($dto->toArray());
        });
    }

    public function markAsRead(int $conversationId, int $userId): void
    {
        Message::where('conversation_id', $conversationId)
            ->where('recipient_id', '!=', $userId)
            ->where('is_read', false)
            ->update(['is_read' => true]);
    }

    public function destroy(int $id): void
    {
        $message = Message::findOrFail($id);
        $message->delete();
    }
}