<?php

namespace Modules\Auth\Services;

use App\Services\BaseService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Auth\Entities\PublisherUpgradeRequest;
use Modules\Auth\Entities\User;
use Modules\Auth\Enums\PublisherType;
use Modules\Core\TemporaryFile\Services\MediaSyncService;

class UpgradeRequestService extends BaseService
{
    public const CACHE_TAG = 'upgrade_requests';

    public function __construct(
        protected MediaSyncService $mediaSyncService
    ) {}

    public function listPending(): LengthAwarePaginator
    {
        return PublisherUpgradeRequest::with(['user:id,name,email'])
            ->filter()
            ->orderBy('created_at', 'asc')
            ->paginate($this->getPerPage());
    }

    public function submitRequest(int $userId, ?array $licenseDocument = null, ?array $commercialRegisterDocument = null): PublisherUpgradeRequest
    {
        $user = User::findOrFail($userId);

        $existingPending = PublisherUpgradeRequest::where('user_id', $userId)
            ->where('status', 'pending')
            ->first();

        if ($existingPending) {
            throw new \RuntimeException('You already have a pending upgrade request.');
        }

        if ($user->publisher_type === PublisherType::OFFICE && $user->is_verified) {
            throw new \RuntimeException('Your office is already verified.');
        }

        return DB::transaction(function () use ($user, $licenseDocument, $commercialRegisterDocument): PublisherUpgradeRequest {
            $request = PublisherUpgradeRequest::create([
                'user_id' => $user->id,
                'status' => 'pending',
            ]);

            if ($licenseDocument) {
                $this->mediaSyncService->syncFiles(
                    model: $user,
                    files: [$licenseDocument],
                    ruleName: 'property_images',
                    collectionName: 'license_document'
                );
            }

            if ($commercialRegisterDocument) {
                $this->mediaSyncService->syncFiles(
                    model: $user,
                    files: [$commercialRegisterDocument],
                    ruleName: 'property_images',
                    collectionName: 'commercial_register_document'
                );
            }

            return $request->load('user:id,name,email');
        });
    }

    public function getStatus(int $userId): ?PublisherUpgradeRequest
    {
        return PublisherUpgradeRequest::where('user_id', $userId)
            ->latest()
            ->first();
    }

    public function approve(int $requestId, int $reviewedBy): PublisherUpgradeRequest
    {
        return DB::transaction(function () use ($requestId, $reviewedBy): PublisherUpgradeRequest {
            $upgradeRequest = PublisherUpgradeRequest::findOrFail($requestId);

            if ($upgradeRequest->status !== 'pending') {
                throw new \RuntimeException('This request has already been processed.');
            }

            $upgradeRequest->update([
                'status' => 'approved',
                'reviewed_by' => $reviewedBy,
                'reviewed_at' => now(),
            ]);

            $user = $upgradeRequest->user;
            $user->publisher_type = PublisherType::OFFICE->value;
            $user->is_verified = true;
            $user->save();

            return $upgradeRequest->load('user:id,name,email');
        });
    }

    public function reject(int $requestId, int $reviewedBy, string $reason): PublisherUpgradeRequest
    {
        return DB::transaction(function () use ($requestId, $reviewedBy, $reason): PublisherUpgradeRequest {
            $upgradeRequest = PublisherUpgradeRequest::findOrFail($requestId);

            if ($upgradeRequest->status !== 'pending') {
                throw new \RuntimeException('This request has already been processed.');
            }

            $upgradeRequest->update([
                'status' => 'rejected',
                'rejection_reason' => $reason,
                'reviewed_by' => $reviewedBy,
                'reviewed_at' => now(),
            ]);

            return $upgradeRequest->load('user:id,name,email');
        });
    }
}
