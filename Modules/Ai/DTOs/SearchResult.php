<?php

namespace Modules\Ai\DTOs;

use App\Interfaces\DTOInterface;

final readonly class SearchResult implements DTOInterface
{
    public function __construct(
        public array $properties,
        public array $extractedRequirements,
        public int $totalMatching,
        public int $returned,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            properties: $data['properties'] ?? [],
            extractedRequirements: $data['extracted_requirements'] ?? [],
            totalMatching: (int) ($data['total_matching'] ?? 0),
            returned: (int) ($data['returned'] ?? 0),
        );
    }

    public function toArray(): array
    {
        return [
            'properties' => $this->properties,
            'extracted_requirements' => $this->extractedRequirements,
            'total_matching' => $this->totalMatching,
            'returned' => $this->returned,
        ];
    }
}
