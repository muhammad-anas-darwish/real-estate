<?php

namespace Modules\RealEstate\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\RealEstate\DTOs\PropertyDTO;
use Modules\RealEstate\Http\Requests\StorePropertyRequest;
use Modules\RealEstate\Http\Requests\UpdatePropertyRequest;
use Modules\RealEstate\Http\Resources\PropertyResource;
use Modules\RealEstate\Services\PropertyService;

class PropertyController extends Controller
{
    public function __construct(protected readonly PropertyService $propertyService)
    {
        $this->applyPermissions(
            'properties',
            ['index', 'show', 'store', 'update', 'destroy'],
            [
                'approve' => 'approve',
                'reject' => 'reject',
                'markAsSold' => 'edit',
            ]
        );
    }

    public function index()
    {
        $properties = $this->propertyService->all();
        return $this->paginatedResponse(PropertyResource::collection($properties));
    }

    public function show($id)
    {
        $property = $this->propertyService->find($id);
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
        $property = $this->propertyService->approve($id, auth()->id());
        return $this->successResponse(PropertyResource::make($property), __('messages.property_approved'));
    }

    public function reject($id)
    {
        $property = $this->propertyService->reject($id, auth()->id());
        return $this->successResponse(PropertyResource::make($property), __('messages.property_rejected'));
    }

    public function markAsSold($id)
    {
        $property = $this->propertyService->markAsSold($id);
        return $this->successResponse(PropertyResource::make($property), __('messages.property_sold'));
    }
}
