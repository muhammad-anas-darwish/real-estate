<?php

namespace Modules\Communication\Http\Resources;

use App\Http\Resources\BaseJsonResource;
use Modules\Communication\Enums\NotificationTypeEnum;

class NotificationResource extends BaseJsonResource
{
    protected function getCustomData(): array
    {
        $type = $this->data['notification_type'] ?? null;

        return [
            'type' => $type,
            'type_label' => $this->getTypeLabel($type),
            'title' => $this->data['title'] ?? null,
            'body' => $this->data['body'] ?? null,
            'data' => $this->data['data'] ?? [],
            'read_at' => $this->formatDate($this->read_at),
            'is_read' => $this->read_at !== null,
        ];
    }

    protected function getTypeLabel(?string $type): string
    {
        if (! $type) {
            return 'إشعار';
        }

        $enum = NotificationTypeEnum::tryFrom($type);

        return $enum?->label() ?? $type;
    }
}