<?php

namespace Modules\Communication\DTOs;

use App\Interfaces\DTOInterface;

final readonly class MessageDTO implements DTOInterface
{
    public function __construct(
        public ?int $conversation_id = null,
        public ?int $sender_id = null,
        public ?string $content = null,
    ) {}

    public static function fromRequest(array $array): self
    {
        return new self(
            conversation_id: $array['conversation_id'] ?? null,
            sender_id: $array['sender_id'] ?? null,
            content: $array['content'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'conversation_id' => $this->conversation_id,
            'sender_id' => $this->sender_id,
            'content' => $this->content,
        ], fn ($value) => $value !== null);
    }
}
