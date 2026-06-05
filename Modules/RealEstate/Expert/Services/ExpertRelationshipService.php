<?php

namespace Modules\RealEstate\Expert\Services;

use App\Services\BaseService;
use Modules\Auth\Entities\User;
use Modules\RealEstate\Expert\Entities\ExpertRelationship;
use Modules\RealEstate\Expert\Enums\ExpertRelationshipStatus;

class ExpertRelationshipService extends BaseService
{
    public const CACHE_TAG = 'expert_relationships';

    public function getRelationship(int $id): ExpertRelationship
    {
        return ExpertRelationship::with(['expert:id,name,email', 'user:id,name,email', 'room'])
            ->findOrFail($id);
    }

    public function getUserRelationships(User $user)
    {
        return ExpertRelationship::forUser($user->id)
            ->with(['expert:id,name,email,expert_type', 'room'])
            ->orderByDesc('created_at')
            ->paginate(15);
    }

    public function getExpertRelationships(User $expert)
    {
        if (!$expert->is_expert) {
            throw new \Exception('User is not an expert');
        }

        return ExpertRelationship::forExpert($expert->id)
            ->with(['user:id,name,email', 'room'])
            ->orderByDesc('created_at')
            ->paginate(15);
    }

    public function cancelRelationship(ExpertRelationship $relationship, User $user): bool
    {
        if ($relationship->expert_id !== $user->id && $relationship->user_id !== $user->id) {
            throw new \Exception('You are not authorized to cancel this relationship');
        }

        if ($relationship->status !== ExpertRelationshipStatus::Active) {
            throw new \Exception('Only active relationships can be cancelled');
        }

        return $relationship->update(['status' => ExpertRelationshipStatus::Cancelled]);
    }

    public function completeRelationship(ExpertRelationship $relationship, User $expert): bool
    {
        if ($relationship->expert_id !== $expert->id) {
            throw new \Exception('Only the expert can complete this relationship');
        }

        if ($relationship->status !== ExpertRelationshipStatus::Active) {
            throw new \Exception('Only active relationships can be completed');
        }

        return $relationship->update(['status' => ExpertRelationshipStatus::Completed]);
    }

    public function getActiveRelationshipsCount(int $expertId): int
    {
        return ExpertRelationship::forExpert($expertId)
            ->active()
            ->count();
    }
}
