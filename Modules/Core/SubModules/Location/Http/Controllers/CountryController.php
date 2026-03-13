<?php

namespace Modules\Core\SubModules\Location\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Core\SubModules\Location\DTOs\CountryDTO;
use Modules\Core\SubModules\Location\Http\Resources\CountryResource;
use Modules\Core\SubModules\Location\Http\Requests\StoreCountryRequest;
use Modules\Core\SubModules\Location\Http\Requests\UpdateCountryRequest;
use Modules\Core\SubModules\Location\Services\CountryService;

class CountryController extends Controller
{
    public function __construct(protected readonly CountryService $countryService)
    {
        $this->applyPermissions(
            'countries',
            ['index', 'show', 'store', 'update', 'destroy']
        );
    }

    public function index()
    {
        $countries = $this->countryService->all();
        return $this->paginatedResponse(CountryResource::collection($countries));
    }

    public function show($id)
    {
        $country = $this->countryService->find($id);
        return $this->successResponse(CountryResource::make($country));
    }

    public function store(StoreCountryRequest $request)
    {
        $country = $this->countryService->store(CountryDTO::fromRequest($request->validated()));
        return $this->successResponse(CountryResource::make($country))->created('country');
    }

    public function update(UpdateCountryRequest $request, $id)
    {
        $country = $this->countryService->update($id, CountryDTO::fromRequest($request->validated()));
        return $this->successResponse(CountryResource::make($country))->updated('country');
    }

    public function destroy($id)
    {
        $this->countryService->destroy($id);
        return $this->successResponse()->deleted('country');
    }
}
