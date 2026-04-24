<?php

namespace Modules\Communication\Services\Fcm;

use App\Interfaces\DTOInterface;

readonly final class FcmPayload implements DTOInterface
{
    public function __construct(
        public string $title,
        public string $body,
        public array $data = [],
        public ?string $imageUrl = null,
        public FcmPriority $priority = FcmPriority::HIGH,
        public ?string $clickAction = null,
    ) {
    }

    public static function fromRequest(array $array): self
    {
        return new self(
            title: $array['title'],
            body: $array['body'],
            data: $array['data'] ?? [],
            imageUrl: $array['imageUrl'] ?? null,
            priority: FcmPriority::tryFrom($array['priority'] ?? 'high') ?? FcmPriority::HIGH,
            clickAction: $array['clickAction'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'notification' => [
                'title' => $this->title,
                'body' => $this->body,
                'image' => $this->imageUrl,
            ],
            'data' => $this->data,
            'priority' => $this->priority->firebaseValue(),
            'click_action' => $this->clickAction,
        ];
    }

    public function toFcmMessage(): array
    {
        $message = [
            'notification' => [
                'title' => $this->title,
                'body' => $this->body,
            ],
            'data' => $this->data,
            'android' => [
                'priority' => $this->priority->firebaseValue(),
            ],
            'webpush' => [
                'headers' => [
                    'TTL' => $this->priority === FcmPriority::HIGH ? '129600' : '432000',
                ],
            ],
        ];

        if ($this->imageUrl) {
            $message['notification']['image'] = $this->imageUrl;
        }

        if ($this->clickAction) {
            $message['click_action'] = $this->clickAction;
        }

        return $message;
    }
}