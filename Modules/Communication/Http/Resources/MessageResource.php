<?php

namespace Modules\Communication\Http\Resources;

use App\Http\Resources\BaseJsonResource;
use Modules\Auth\Http\Resources\UserResource;

class MessageResource extends BaseJsonResource
{
    protected function getRelationMap(): array
    {
        return [
            'sender' => UserResource::class,
            'conversation' => ConversationResource::class,
        ];
    }

    protected function getCustomData(): array
    {
        return [
            'conversation_id' => $this->conversation_id,
            'sender_id' => $this->sender_id,
            'content' => $this->content,
            'is_read' => $this->is_read,
        ];
    }
}