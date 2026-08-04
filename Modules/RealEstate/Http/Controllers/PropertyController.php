<?php

namespace Modules\RealEstate\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Modules\RealEstate\DTOs\PropertyDTO;
use Modules\RealEstate\Enums\PropertyStatus;
use Modules\RealEstate\Http\Requests\AdvancedPropertyFilterRequest;
use Modules\RealEstate\Http\Requests\StorePropertyRequest;
use Modules\RealEstate\Http\Requests\UpdatePropertyRequest;
use Modules\RealEstate\Http\Requests\UpdatePropertyStatusRequest;
use Modules\RealEstate\Http\Resources\PropertyResource;
use Modules\RealEstate\Services\PropertyService;
use Modules\RealEstate\Services\PropertyStatusService;
use Modules\ServiceProvider\DTOs\ServiceRequestDTO;
use Modules\ServiceProvider\Http\Resources\ServiceRequestResource;
use Modules\ServiceProvider\Services\ServiceRequestService;

class PropertyController extends Controller
{
    public function __construct(
        protected readonly PropertyService $propertyService,
        protected readonly PropertyStatusService $statusService,
        protected readonly ServiceRequestService $serviceRequestService
    ) {
        $this->applyPermissions(
            'properties',
            ['store', 'update', 'destroy'],
            [
                'toggleFavorite' => 'list',
                'statistics' => 'list',
                'updateStatus' => 'edit',
            ]
        );
    }

    public function indexPublic(AdvancedPropertyFilterRequest $request)
    {
        $properties = $this->propertyService->publicProperties(Auth::id());

        return $this->paginatedResponse(PropertyResource::collection($properties));
    }

    public function showPublic($id)
    {
        $property = $this->propertyService->findPublic($id, Auth::id());

        return $this->successResponse(PropertyResource::make($property));
    }

    public function indexDashboard()
    {
        $properties = $this->propertyService->dashboardProperties(Auth::id());

        return $this->paginatedResponse(PropertyResource::collection($properties));
    }

    public function showDashboard($id)
    {
        $property = $this->propertyService->findDashboard($id, Auth::id());

        return $this->successResponse(PropertyResource::make($property));
    }

    public function store(StorePropertyRequest $request)
    {
        $dto = PropertyDTO::fromRequest($request->validated());
        $property = $this->propertyService->store($dto);

        return $this->successResponse(PropertyResource::make($property))->created('property');
    }

    public function update(UpdatePropertyRequest $request, $id)
    {
        $dto = PropertyDTO::fromRequest($request->validated());
        $property = $this->propertyService->update($id, $dto);

        return $this->successResponse(PropertyResource::make($property))->updated('property');
    }

    public function destroy($id)
    {
        $this->propertyService->destroy($id);

        return $this->successResponse()->deleted('property');
    }

    public function myProperties()
    {
        $properties = $this->propertyService->myProperties(Auth::id());

        return $this->paginatedResponse(PropertyResource::collection($properties));
    }

    public function toggleFavorite($id)
    {
        /** @var \Modules\Auth\Entities\User $user */
        $user = Auth::user();
        if (! $user) {
            return $this->unauthorizedResponse();
        }

        $user->lovedProperties()->toggle($id);

        return $this->successResponse([], 'Property favorite status updated');
    }

    public function random()
    {
        $properties = $this->propertyService->random(10, Auth::id());

        return $this->successResponse(PropertyResource::collection($properties));
    }

    public function statistics()
    {
        return $this->successResponse($this->propertyService->statistics());
    }

    public function updateStatus(UpdatePropertyStatusRequest $request, $id)
    {
        $property = $this->propertyService->findDashboard($id);
        $validated = $request->validated();
        $newStatus = PropertyStatus::from($validated['status']);
        $rejectionReason = $validated['rejection_reason'] ?? null;

        $user = Auth::user();
        $policy = Gate::getPolicyFor($property);

        if ($policy && ! $policy->updateStatus($user, $property, $newStatus)) {
            return $this->forbiddenResponse();
        }

        $this->statusService->handle($property, $newStatus, $rejectionReason);

        return $this->successResponse(
            PropertyResource::make($property->fresh(['publisher', 'approver', 'media'])),
            __('messages.property_status_updated')
        );
    }

    public function storeWithPhotographer(StorePropertyRequest $request)
    {
        $propertyDto = PropertyDTO::fromRequest(
            array_merge($request->validated(), ['status' => PropertyStatus::DRAFT->value])
        );

        $property = $this->propertyService->store($propertyDto);

        $serviceDto = ServiceRequestDTO::fromRequest([
            'service_type' => 'photography',
            'property_id' => $property->id,
            'provider_id' => request('provider_id'),
            'scheduled_at' => request('scheduled_at'),
            'client_notes' => request('photographer_notes'),
            'price' => request('photographer_price'),
        ]);

        $serviceRequest = $this->serviceRequestService->create(Auth::id(), $serviceDto);

        return $this->successResponse([
            'property' => PropertyResource::make($property),
            'service_request' => ServiceRequestResource::make($serviceRequest),
        ], 'Property saved as draft and photographer requested')->created('property');
    }
}
