<?php

namespace Modules\Crm\DTOs;

use App\Interfaces\DTOInterface;

readonly final class LeadNoteDTO implements DTOInterface
{
    public function __construct(
        public int $lead_id,
        public int $author_id,
        public string $body,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            lead_id: $data['lead_id'],
            author_id: $data['author_id'] ?? auth()->id(),
            body: $data['body'],
        );
    }

    public function toArray(): array
    {
        return [
            'lead_id' => $this->lead_id,
            'author_id' => $this->author_id,
            'body' => $this->body,
        ];
    }
}
