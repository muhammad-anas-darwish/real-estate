<?php

namespace Modules\Communication\Notifications;

use Modules\Communication\Enums\NotificationTypeEnum;
use Modules\Communication\Services\Fcm\FcmPayload;
use Modules\Communication\Services\Fcm\FcmPriority;

class NewMessageNotification extends BaseNotification
{
    public function __construct(
        public readonly string $senderName,
        public readonly string $messagePreview,
        public readonly int $roomId,
    ) {}

    public function getNotificationType(): NotificationTypeEnum
    {
        return NotificationTypeEnum::NEW_MESSAGE;
    }

    protected function getTitle(): string
    {
        return 'رسالة جديدة من ' . $this->senderName;
    }

    protected function getBody(): string
    {
        return $this->messagePreview;
    }

    protected function toFcmData(): array
    {
        return [
            'notification_type' => $this->getNotificationType()->value,
            'room_id' => (string) $this->roomId,
            'sender_name' => $this->senderName,
        ];
    }

    protected function toArrayData(): array
    {
        return [
            'room_id' => $this->roomId,
            'sender_name' => $this->senderName,
            'message_preview' => $this->messagePreview,
        ];
    }

    protected function getPriority(): FcmPriority
    {
        return FcmPriority::HIGH;
    }
}