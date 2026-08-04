<?php

namespace Modules\RealEstate\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\RealEstate\Entities\Ad;
use Modules\RealEstate\Http\Requests\CreateAdRequest;
use Modules\RealEstate\Http\Requests\UpdateAdRequest;
use Modules\RealEstate\Http\Resources\AdResource;
use Modules\RealEstate\Services\AdGroupService;
use Modules\RealEstate\Services\AdService;

class AdController extends Controller
{
    public function __construct(
        protected readonly AdService $adService,
        protected readonly AdGroupService $adGroupService
    ) {
        $this->applyPermissions(
            'ads',
            ['store', 'update', 'destroy'],
            [
                'index' => 'list',
                'show' => 'show',
                'setStatus' => 'edit',
                'archive' => 'delete',
                'restore' => 'edit',
                'linkProperty' => 'edit',
                'unlinkProperty' => 'edit',
            ]
        );
    }

    public function index()
    {
        $user = Auth::user();

        if ($user->hasRole('super-admin')) {
            $ads = $this->adService->getAll(request()->all());
        } else {
            $ads = $this->adService->getForUser($user->id);
        }

        return $this->paginatedResponse(AdResource::collection($ads));
    }

    public function store(CreateAdRequest $request)
    {
        $ad = $this->adService->create($request->validated());

        return $this->successResponse(AdResource::make($ad))->created('ad');
    }

    public function show($id)
    {
        $ad = Ad::with(['adGroup', 'creator', 'media', 'property'])
            ->findOrFail($id);

        $this->authorizeOwnership($ad);

        return $this->successResponse(AdResource::make($ad));
    }

    public function update(UpdateAdRequest $request, $id)
    {
        $ad = Ad::findOrFail($id);
        $this->authorizeOwnership($ad);

        $ad = $this->adService->update((int) $id, $request->validated());

        return $this->successResponse(AdResource::make($ad))->updated('ad');
    }

    public function setStatus($id)
    {
        $ad = Ad::findOrFail($id);
        $this->authorizeOwnership($ad);

        $validated = request()->validate(['status' => 'required|string|in:draft,active,paused']);
        $ad = $this->adService->setStatus((int) $id, $validated['status']);

        return $this->successResponse(AdResource::make($ad), __('messages.ad_status_updated'));
    }

    public function archive($id)
    {
        $ad = Ad::findOrFail($id);
        $this->authorizeOwnership($ad);

        $this->adService->archive((int) $id);

        return $this->successResponse()->deleted('ad');
    }

    public function restore($id)
    {
        $ad = Ad::withTrashed()->findOrFail($id);
        $this->authorizeOwnership($ad);

        $this->adService->restore((int) $id);
        $ad = Ad::with(['adGroup', 'creator', 'media', 'property'])->findOrFail($id);

        return $this->successResponse(AdResource::make($ad), __('crud.restored', ['model' => __('models.ad')]));
    }

    public function linkProperty($id, $propertyId = null)
    {
        $ad = Ad::findOrFail($id);
        $this->authorizeOwnership($ad);

        $propertyId ??= request('property_id');
        $ad = $this->adService->linkToProperty((int) $id, (int) $propertyId);

        return $this->successResponse(AdResource::make($ad), __('messages.ad_linked_to_property'));
    }

    public function unlinkProperty($id)
    {
        $ad = Ad::findOrFail($id);
        $this->authorizeOwnership($ad);

        $ad = $this->adService->unlinkProperty((int) $id);

        return $this->successResponse(AdResource::make($ad), __('messages.ad_unlinked_from_property'));
    }

    private function authorizeOwnership(Ad $ad): void
    {
        $user = Auth::user();

        if ($user->hasRole('super-admin')) {
            return;
        }

        if ($ad->created_by !== $user->id) {
            throw new \Illuminate\Http\Exceptions\HttpResponseException(
                response()->json([
                    'success' => false,
                    'message' => 'Forbidden',
                    'data' => null,
                ], 403)
            );
        }
    }
}
