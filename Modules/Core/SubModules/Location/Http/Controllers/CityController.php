<?php

namespace Modules\Core\SubModules\Location\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Core\SubModules\Location\DTOs\CityDTO;
use Modules\Core\SubModules\Location\Http\Resources\CityResource;
use Modules\Core\SubModules\Location\Http\Requests\StoreCityRequest;
use Modules\Core\SubModules\Location\Http\Requests\UpdateCityRequest;
use Modules\Core\SubModules\Location\Services\CityService;

class CityController extends Controller
{
    public function __construct(protected readonly CityService $cityService)
    {
        $this->applyPermissions(
            'cities',
            ['index', 'show', 'store', 'update', 'destroy']
        );
    }

    public function index()
    {
        $cities = $this->cityService->all();
        return $this->paginatedResponse(CityResource::collection($cities));
    }

    public function show($id)
    {
        $city = $this->cityService->find($id);
        return $this->successResponse(CityResource::make($city));
    }

    public function store(StoreCityRequest $request)
    {
        $city = $this->cityService->store(CityDTO::fromRequest($request->validated()));
        return $this->successResponse(CityResource::make($city))->created('city');
    }

    public function update(UpdateCityRequest $request, $id)
    {
        $city = $this->cityService->update($id, CityDTO::fromRequest($request->validated()));
        return $this->successResponse(CityResource::make($city))->updated('city');
    }

    public function destroy($id)
    {
        $this->cityService->destroy($id);
        return $this->successResponse()->deleted('city');
    }
}
