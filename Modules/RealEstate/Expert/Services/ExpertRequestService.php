<?php

namespace Modules\RealEstate\Expert\Services;

use App\Services\BaseService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Auth\Entities\User;
use Modules\Communication\Entities\ChatRoom;
use Modules\Communication\Enums\RoomTypeEnum;
use Modules\Communication\Services\ChatService;
use Modules\RealEstate\Expert\Entities\ExpertRelationship;
use Modules\RealEstate\Expert\Entities\ExpertRequest;
use Modules\RealEstate\Expert\Enums\ExpertRelationshipStatus;
use Modules\RealEstate\Expert\Enums\ExpertRequestStatus;
use Modules\RealEstate\Expert\Enums\ExpertType;

class ExpertRequestService extends BaseService
{
    public const CACHE_TAG = 'expert_requests';

    public function __construct(
        protected readonly ChatService $chatService,
    ) {}

    public function createRequest(User $user, ExpertType $expertType, ?string $message = null): ExpertRequest
    {
        $pendingRequest = ExpertRequest::where('user_id', $user->id)
            ->where('expert_type', $expertType->value)
            ->where('status', ExpertRequestStatus::Pending->value)
            ->first();

        if ($pendingRequest) {
            throw new \Exception('You already have a pending request for this expert type');
        }

        $existingResolved = ExpertRequest::where('user_id', $user->id)
            ->where('expert_type', $expertType->value)
            ->where('status', ExpertRequestStatus::Resolved->value)
            ->whereHas('relationship', fn ($q) => $q->where('status', ExpertRelationshipStatus::Active->value))
            ->first();

        if ($existingResolved) {
            throw new \Exception('You already have an active relationship with an expert of this type');
        }

        return DB::transaction(function () use ($user, $expertType, $message) {
            $request = ExpertRequest::create([
                'user_id' => $user->id,
                'expert_type' => $expertType->value,
                'message' => $message,
                'status' => ExpertRequestStatus::Pending->value,
            ]);

            $expert = $this->findAvailableExpert($expertType);

            if ($expert) {
                $this->createRelationship($request, $expert);
                $request->update(['status' => ExpertRequestStatus::Resolved->value]);
            }

            return $request->fresh(['relationship', 'relationship.room']);
        });
    }

    public function findAvailableExpert(ExpertType $expertType): ?User
    {
        $experts = User::where('is_expert', true)
            ->where('expert_type', $expertType->value)
            ->where('status', 'active')
            ->withCount([
                'expertRelationships as active_count' => fn ($q) => $q->where('status', ExpertRelationshipStatus::Active->value),
            ])
            ->get();

        $availableExperts = $experts->filter(function ($expert) {
            return $expert->max_users === -1 || $expert->active_count < $expert->max_users;
        });

        if ($availableExperts->isEmpty()) {
            return null;
        }

        return $availableExperts->sortBy('active_count')->first();
    }

    protected function createRelationship(ExpertRequest $request, User $expert): ExpertRelationship
    {
        return DB::transaction(function () use ($request, $expert) {
            $room = $this->chatService->startPrivateChat($request->user, $expert);

            $relationship = ExpertRelationship::create([
                'expert_id' => $expert->id,
                'user_id' => $request->user_id,
                'room_id' => $room->id,
                'status' => ExpertRelationshipStatus::Active,
            ]);

            return $relationship;
        });
    }

    public function cancelRequest(ExpertRequest $request, User $user): bool
    {
        if ($request->user_id !== $user->id) {
            throw new \Exception('You can only cancel your own requests');
        }

        if ($request->status->value !== ExpertRequestStatus::Pending->value) {
            throw new \Exception('Only pending requests can be cancelled');
        }

        return $request->update(['status' => ExpertRequestStatus::Resolved->value]);
    }

    public function getPendingRequests()
    {
        return ExpertRequest::pending()
            ->with(['user:id,name,email', 'relationship'])
            ->orderByDesc('created_at')
            ->paginate(15);
    }

    public function getRequest(int $id): ExpertRequest
    {
        return ExpertRequest::with(['user:id,name,email', 'relationship', 'relationship.room'])
            ->findOrFail($id);
    }

    public function getUserRequests(User $user)
    {
        return ExpertRequest::where('user_id', $user->id)
            ->with(['relationship', 'relationship.room'])
            ->orderByDesc('created_at')
            ->paginate(15);
    }
}
