<?php

namespace Modules\RealEstate\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\RealEstate\DTOs\PropertyDTO;
use Modules\RealEstate\Enums\PropertyStatus;
use Modules\RealEstate\Http\Requests\StorePropertyRequest;
use Modules\RealEstate\Http\Requests\UpdatePropertyRequest;
use Modules\RealEstate\Http\Requests\UpdatePropertyStatusRequest;
use Modules\RealEstate\Http\Resources\PropertyResource;
use Modules\RealEstate\Services\PropertyService;
use Modules\RealEstate\Services\PropertyStatusService;

class PropertyController extends Controller
{
    public function __construct(
        protected readonly PropertyService $propertyService,
        protected readonly PropertyStatusService $statusService
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

    public function indexPublic()
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
        $newStatus = PropertyStatus::from($request->validated()['status']);

        $this->authorize('updateStatus', [$property, $newStatus]);

        $this->statusService->handle($property, $newStatus);

        return $this->successResponse(
            PropertyResource::make($property->fresh(['publisher', 'approver', 'media'])),
            __('messages.property_status_updated')
        );
    }
}
