<?php

namespace Modules\RealEstate\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\RealEstate\DTOs\PropertyDTO;
use Modules\RealEstate\Http\Requests\StorePropertyRequest;
use Modules\RealEstate\Http\Requests\UpdatePropertyRequest;
use Modules\RealEstate\Http\Requests\UpdatePropertyStatusRequest;
use Modules\RealEstate\Http\Resources\PropertyResource;
use Modules\RealEstate\Services\PropertyService;

class PropertyController extends Controller
{
    public function __construct(protected readonly PropertyService $propertyService)
    {
        $this->applyPermissions(
            'properties',
            ['store', 'update', 'destroy'],
            [
                'approve' => 'approve',
                'reject' => 'reject',
                'markAsSold' => 'edit',
                'toggleFavorite' => 'list',
                'statistics' => 'list',
                'archive' => 'archive',
                'restore' => 'restore',
                'updateStatus' => 'edit',
            ]
        );
    }

    public function index()
    {
        $properties = $this->propertyService->all(Auth::id());

        return $this->paginatedResponse(PropertyResource::collection($properties));
    }

    public function show($id)
    {
        $property = $this->propertyService->find($id, Auth::id());

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

    public function approve($id)
    {
        $property = $this->propertyService->approve($id, Auth::id());

        return $this->successResponse(PropertyResource::make($property), __('messages.property_approved'));
    }

    public function reject($id)
    {
        $property = $this->propertyService->reject($id, Auth::id());

        return $this->successResponse(PropertyResource::make($property), __('messages.property_rejected'));
    }

    public function markAsSold($id)
    {
        $property = $this->propertyService->markAsSold($id);

        return $this->successResponse(PropertyResource::make($property), __('messages.property_sold'));
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

    public function archive($id)
    {
        $property = $this->propertyService->archive($id);

        return $this->successResponse(PropertyResource::make($property), __('messages.property_archived'));
    }

    public function restore($id)
    {
        $property = $this->propertyService->restore($id);

        return $this->successResponse(PropertyResource::make($property), __('messages.property_restored'));
    }

    public function updateStatus(UpdatePropertyStatusRequest $request, $id)
    {
        $property = $this->propertyService->updateStatus($id, $request->validated()['status']);

        return $this->successResponse(PropertyResource::make($property), __('messages.property_status_updated'));
    }
}
