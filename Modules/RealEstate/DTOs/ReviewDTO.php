<?php

namespace Modules\RealEstate\DTOs;

use App\Interfaces\DTOInterface;

final readonly class ReviewDTO implements DTOInterface
{
    public function __construct(
        public ?int $reviewed_id = null,
        public ?int $rating = null,
        public ?string $comment = null,
        public ?int $property_id = null,
    ) {}

    public static function fromRequest(array $array): self
    {
        return new self(
            reviewed_id: $array['reviewed_id'] ?? null,
            rating: $array['rating'] ?? null,
            comment: $array['comment'] ?? null,
            property_id: $array['property_id'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'reviewed_id' => $this->reviewed_id,
            'rating' => $this->rating,
            'comment' => $this->comment,
            'property_id' => $this->property_id,
        ], fn ($value) => $value !== null);
    }
}
