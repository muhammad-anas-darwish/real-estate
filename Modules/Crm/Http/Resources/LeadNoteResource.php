<?php

namespace Modules\Crm\Http\Resources;

use App\Http\Resources\BaseJsonResource;
use Modules\Auth\Http\Resources\UserResource;

class LeadNoteResource extends BaseJsonResource
{
    protected function getCustomData(): array
    {
        return [
            'lead_id' => $this->lead_id,
            'body' => $this->body,
            'is_locked' => $this->is_locked,
            'is_editable' => $this->isEditableBy(auth()->user()),
            'is_deletable' => $this->isDeletableBy(auth()->user()),
            'author' => [
                'id' => $this->author?->id,
                'name' => $this->author?->name,
            ],
        ];
    }

    protected function getRelationMap(): array
    {
        return [
            'author' => UserResource::class,
        ];
    }
}
