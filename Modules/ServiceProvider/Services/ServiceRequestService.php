<?php

namespace Modules\ServiceProvider\Services;

use App\Services\BaseService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\RealEstate\Entities\Property;
use Modules\RealEstate\Enums\PropertyStatus;
use Modules\ServiceProvider\DTOs\ServiceRequestDTO;
use Modules\ServiceProvider\Entities\ServiceProviderProfile;
use Modules\ServiceProvider\Entities\ServiceRequest;
use Modules\ServiceProvider\Entities\ServiceRequestTask;
use Modules\ServiceProvider\Enums\ServiceRequestStatus;
use Modules\ServiceProvider\Enums\ServiceType;

class ServiceRequestService extends BaseService
{
    public const CACHE_TAG = 'service_requests';

    public function create(int $clientId, ServiceRequestDTO $dto): ServiceRequest
    {
        return DB::transaction(function () use ($clientId, $dto): ServiceRequest {
            $data = $dto->toArray();
            $data['client_id'] = $clientId;
            $data['status'] = ServiceRequestStatus::PENDING;

            if ($dto->provider_id) {
                $provider = ServiceProviderProfile::findOrFail($dto->provider_id);
                if ($provider->price_type->value !== 'negotiable' && ! $dto->price) {
                    $data['price'] = $provider->price_per_task;
                }
            }

            $request = ServiceRequest::create($data);

            if ($dto->service_type === ServiceType::INSPECTION->value && $dto->property_id) {
                $property = Property::find($dto->property_id);
                if ($property) {
                    $property->update([
                        'status' => PropertyStatus::UNDER_INSPECTION,
                        'inspection_requested_at' => now(),
                    ]);
                }
            }

            return $request->load(['client:id,name,email,phone', 'provider.user', 'property']);
        });
    }

    public function listForClient(int $clientId): LengthAwarePaginator
    {
        return ServiceRequest::query()
            ->forClient($clientId)
            ->with(['provider.user:id,name,email,phone', 'property', 'tasks'])
            ->filter()
            ->latest()
            ->paginate($this->getPerPage());
    }

    public function listForProvider(int $providerId): LengthAwarePaginator
    {
        return ServiceRequest::query()
            ->forProvider($providerId)
            ->with(['client:id,name,email,phone', 'property', 'tasks'])
            ->filter()
            ->latest()
            ->paginate($this->getPerPage());
    }

    public function listAllForAdmin(): LengthAwarePaginator
    {
        return ServiceRequest::query()
            ->with(['client:id,name,email,phone', 'provider.user:id,name,email', 'property', 'tasks'])
            ->filter()
            ->latest()
            ->paginate($this->getPerPage());
    }

    public function find(int $id): ServiceRequest
    {
        return ServiceRequest::with([
            'client:id,name,email,phone',
            'provider.user:id,name,email,phone',
            'provider.coverageAreas.city',
            'property',
            'tasks',
        ])->findOrFail($id);
    }

    public function accept(int $id, int $providerId): ServiceRequest
    {
        return DB::transaction(function () use ($id, $providerId): ServiceRequest {
            $request = ServiceRequest::findOrFail($id);

            if (! $request->status->canTransitionTo(ServiceRequestStatus::ACCEPTED)) {
                throw new \RuntimeException("Cannot accept a request in '{$request->status->value}' status.");
            }

            if ($request->provider_id && $request->provider_id !== $providerId) {
                throw new \RuntimeException('This request was already assigned to another provider.');
            }

            $request->update([
                'provider_id' => $providerId,
                'status' => ServiceRequestStatus::ACCEPTED,
            ]);

            return $request->load(['client:id,name,email,phone', 'provider.user', 'property']);
        });
    }

    public function reject(int $id, int $providerId): ServiceRequest
    {
        return DB::transaction(function () use ($id): ServiceRequest {
            $request = ServiceRequest::findOrFail($id);

            if (! $request->status->canTransitionTo(ServiceRequestStatus::REJECTED)) {
                throw new \RuntimeException("Cannot reject a request in '{$request->status->value}' status.");
            }

            $request->update(['status' => ServiceRequestStatus::REJECTED]);

            return $request->load(['client:id,name,email,phone']);
        });
    }

    public function startProgress(int $id, int $providerId): ServiceRequest
    {
        return DB::transaction(function () use ($id, $providerId): ServiceRequest {
            $request = ServiceRequest::where('provider_id', $providerId)->findOrFail($id);

            if (! $request->status->canTransitionTo(ServiceRequestStatus::IN_PROGRESS)) {
                throw new \RuntimeException("Cannot start work on a request in '{$request->status->value}' status.");
            }

            $request->update(['status' => ServiceRequestStatus::IN_PROGRESS]);

            return $request->load(['client:id,name,email,phone', 'tasks']);
        });
    }

    public function complete(int $id, int $providerId, ?string $providerNotes = null): ServiceRequest
    {
        return DB::transaction(function () use ($id, $providerId, $providerNotes): ServiceRequest {
            $request = ServiceRequest::where('provider_id', $providerId)->findOrFail($id);

            if (! $request->status->canTransitionTo(ServiceRequestStatus::COMPLETED)) {
                throw new \RuntimeException("Cannot complete a request in '{$request->status->value}' status.");
            }

            $request->update([
                'status' => ServiceRequestStatus::COMPLETED,
                'completed_at' => now(),
                'provider_notes' => $providerNotes,
            ]);

            if ($request->price && $request->price > 0 && ! $request->is_paid) {
                app(ProviderBillingService::class)->settlePayment($request);
            }

            if ($request->service_type === ServiceType::INSPECTION && $request->property_id) {
                $property = Property::find($request->property_id);
                if ($property) {
                    $property->update([
                        'inspection_completed_at' => now(),
                        'status' => PropertyStatus::PENDING,
                    ]);
                }
            }

            $this->updateProviderStats($providerId);

            return $request->load(['client:id,name,email,phone', 'tasks']);
        });
    }

    public function cancel(int $id, int $userId): ServiceRequest
    {
        return DB::transaction(function () use ($id, $userId): ServiceRequest {
            $request = ServiceRequest::where(function ($q) use ($userId) {
                $q->where('client_id', $userId)
                    ->orWhereHas('provider', fn ($pq) => $pq->where('user_id', $userId));
            })->findOrFail($id);

            if (! $request->status->canTransitionTo(ServiceRequestStatus::CANCELLED)) {
                throw new \RuntimeException("Cannot cancel a request in '{$request->status->value}' status.");
            }

            $request->update([
                'status' => ServiceRequestStatus::CANCELLED,
                'cancelled_at' => now(),
            ]);

            return $request->load(['client:id,name,email,phone']);
        });
    }

    public function completeTask(int $taskId, ?array $checklistData = null): ServiceRequestTask
    {
        return DB::transaction(function () use ($taskId, $checklistData): ServiceRequestTask {
            $task = ServiceRequestTask::findOrFail($taskId);

            $update = ['completed_at' => now()];

            if ($checklistData !== null) {
                $update['checklist_json'] = $checklistData;
            }

            $task->update($update);

            return $task;
        });
    }

    public function createTask(int $requestId, ServiceType $taskType, ?array $checklistData = null): ServiceRequestTask
    {
        return ServiceRequestTask::create([
            'service_request_id' => $requestId,
            'task_type' => $taskType->value,
            'checklist_json' => $checklistData,
        ]);
    }

    private function updateProviderStats(int $providerId): void
    {
        $profile = ServiceProviderProfile::find($providerId);
        if ($profile) {
            $completedCount = ServiceRequest::where('provider_id', $providerId)
                ->where('status', ServiceRequestStatus::COMPLETED)
                ->count();

            $profile->update(['total_completed_tasks' => $completedCount]);
        }
    }
}
