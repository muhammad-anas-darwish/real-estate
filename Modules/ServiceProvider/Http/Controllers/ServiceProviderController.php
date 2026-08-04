<?php

namespace Modules\ServiceProvider\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\ServiceProvider\DTOs\ServiceProviderProfileDTO;
use Modules\ServiceProvider\Http\Requests\StoreServiceProviderProfileRequest;
use Modules\ServiceProvider\Http\Requests\UpdateServiceProviderProfileRequest;
use Modules\ServiceProvider\Http\Resources\ServiceProviderResource;
use Modules\ServiceProvider\Services\ServiceProviderService;

class ServiceProviderController extends Controller
{
    public function __construct(
        protected readonly ServiceProviderService $serviceProviderService
    ) {
        $this->applyPermissions(
            'service_providers',
            [],
            [
                'verify' => 'verify',
                'unverify' => 'unverify',
            ]
        );
    }

    public function indexPublic()
    {
        $type = request('type');
        $cityId = request('city_id');

        $providers = $this->serviceProviderService->list($type, $cityId ? (int) $cityId : null);

        return $this->paginatedResponse(ServiceProviderResource::collection($providers));
    }

    public function showPublic($id)
    {
        $provider = $this->serviceProviderService->find((int) $id);

        return $this->successResponse(ServiceProviderResource::make($provider));
    }

    public function register(StoreServiceProviderProfileRequest $request)
    {
        $dto = ServiceProviderProfileDTO::fromRequest($request->validated());
        $profile = $this->serviceProviderService->register(Auth::id(), $dto);

        return $this->successResponse(
            ServiceProviderResource::make($profile),
            'Service provider profile registered successfully'
        )->created('service_provider');
    }

    public function myProfile()
    {
        $profile = Auth::user()->serviceProviderProfile;

        if (! $profile) {
            return $this->failedResponse('You do not have a service provider profile.', 404);
        }

        return $this->successResponse(ServiceProviderResource::make($profile));
    }

    public function updateProfile(UpdateServiceProviderProfileRequest $request)
    {
        $profile = Auth::user()->serviceProviderProfile;

        if (! $profile) {
            return $this->failedResponse('You do not have a service provider profile.', 404);
        }

        $dto = ServiceProviderProfileDTO::fromRequest($request->validated());
        $profile = $this->serviceProviderService->update($profile->id, $dto);

        return $this->successResponse(
            ServiceProviderResource::make($profile),
            'Service provider profile updated successfully'
        )->updated('service_provider');
    }

    public function toggleAvailability()
    {
        $profile = Auth::user()->serviceProviderProfile;

        if (! $profile) {
            return $this->failedResponse('You do not have a service provider profile.', 404);
        }

        $profile = $this->serviceProviderService->toggleAvailability($profile->id);

        return $this->successResponse(
            ServiceProviderResource::make($profile),
            'Availability toggled successfully'
        );
    }

    public function adminIndex()
    {
        $providers = $this->serviceProviderService->adminList();

        return $this->paginatedResponse(ServiceProviderResource::collection($providers));
    }

    public function verify($id)
    {
        $profile = $this->serviceProviderService->verify((int) $id);

        return $this->successResponse(
            ServiceProviderResource::make($profile),
            'Service provider verified successfully'
        );
    }

    public function unverify($id)
    {
        $profile = $this->serviceProviderService->unverify((int) $id);

        return $this->successResponse(
            ServiceProviderResource::make($profile),
            'Service provider verification revoked'
        );
    }
}
