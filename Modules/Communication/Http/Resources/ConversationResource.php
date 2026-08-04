<?php

namespace Modules\Communication\Http\Resources;

use App\Http\Resources\BaseJsonResource;
use Modules\Auth\Http\Resources\UserResource;

class ConversationResource extends BaseJsonResource
{
    protected function getRelationMap(): array
    {
        return [
            'initiator' => UserResource::class,
            'recipient' => UserResource::class,
            'property' => \Modules\RealEstate\Http\Resources\PropertyResource::class,
            'messages' => MessageResource::class,
        ];
    }

    protected function getCustomData(): array
    {
        return [
            'property_id' => $this->property_id,
            'type' => $this->type,
            'initiator_id' => $this->initiator_id,
            'recipient_id' => $this->recipient_id,
            'last_message' => $this->whenLoaded('messages', fn () => $this->messages->first()),
        ];
    }
}
