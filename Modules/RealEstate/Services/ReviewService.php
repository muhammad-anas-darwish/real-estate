<?php

namespace Modules\RealEstate\Services;

use App\Services\BaseService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Auth\Entities\User;
use Modules\Communication\Entities\ChatRoom;
use Modules\RealEstate\DTOs\ReviewDTO;
use Modules\RealEstate\Entities\Review;

class ReviewService extends BaseService
{
    public const CACHE_TAG = 'reviews';

    public function listForOffice(int $officeId): LengthAwarePaginator
    {
        return Review::where('reviewed_id', $officeId)
            ->with(['reviewer:id,name', 'property:id,name'])
            ->orderBy('created_at', 'desc')
            ->paginate($this->getPerPage());
    }

    public function store(int $reviewerId, ReviewDTO $dto): Review
    {
        $canReview = $this->hasCommunicatedWith($reviewerId, $dto->reviewed_id);

        if (! $canReview) {
            throw new \RuntimeException('You can only review offices you have communicated with.');
        }

        $existingReview = Review::where('reviewer_id', $reviewerId)
            ->where('reviewed_id', $dto->reviewed_id)
            ->first();

        if ($existingReview) {
            throw new \RuntimeException('You have already reviewed this office.');
        }

        return DB::transaction(function () use ($reviewerId, $dto): Review {
            $review = Review::create([
                'reviewer_id' => $reviewerId,
                'reviewed_id' => $dto->reviewed_id,
                'rating' => $dto->rating,
                'comment' => $dto->comment,
                'property_id' => $dto->property_id,
            ]);

            $this->updateAverageRating($dto->reviewed_id);

            return $review->load(['reviewer:id,name', 'property:id,name']);
        });
    }

    public function destroy(int $reviewId, int $userId): void
    {
        DB::transaction(function () use ($reviewId, $userId): void {
            $review = Review::findOrFail($reviewId);

            if ($review->reviewer_id !== $userId) {
                throw new \RuntimeException('You can only delete your own reviews.');
            }

            $officeId = $review->reviewed_id;
            $review->delete();

            $this->updateAverageRating($officeId);
        });
    }

    private function updateAverageRating(int $officeId): void
    {
        $avg = Review::where('reviewed_id', $officeId)->avg('rating') ?? 0;

        User::where('id', $officeId)->update(['average_rating' => round($avg, 2)]);
    }

    private function hasCommunicatedWith(int $userId, int $officeId): bool
    {
        return ChatRoom::whereHas('participants', fn ($q) => $q->where('user_id', $userId))
            ->whereHas('participants', fn ($q) => $q->where('user_id', $officeId))
            ->exists();
    }
}
