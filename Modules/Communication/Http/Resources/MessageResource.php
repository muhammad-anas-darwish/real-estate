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
            'parent' => MessageResource::class,
        ];
    }

    protected function getCustomData(): array
    {
        $authUser = auth()->user();

        return [
            'room_id' => $this->room_id,
            'body' => $this->body,
            'type' => $this->type->value,
            'is_mine' => $authUser && $this->sender_id === $authUser->id,
            'is_read' => $this->read_at !== null,
            'attachment_url' => $this->getAttachmentUrl(),
            'parent_message' => $this->when($this->parent_id && $this->relationLoaded('parent'), function () {
                return new MessageResource($this->parent);
            }),
            'created_at' => $this->formatDate($this->created_at),
            'read_at' => $this->formatDate($this->read_at),
        ];
    }

    protected function getAttachmentUrl(): ?string
    {
        return null;
    }
}