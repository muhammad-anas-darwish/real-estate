<?php

namespace Modules\RealEstate\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\RealEstate\Entities\AdGroup;
use Modules\RealEstate\Http\Requests\CreateAdGroupRequest;
use Modules\RealEstate\Http\Requests\SetDefaultAdRequest;
use Modules\RealEstate\Http\Requests\UpdateAdGroupRequest;
use Modules\RealEstate\Http\Resources\AdGroupResource;
use Modules\RealEstate\Services\AdGroupService;

class AdGroupController extends Controller
{
    public function __construct(
        protected readonly AdGroupService $adGroupService
    ) {}

    public function index()
    {
        $groups = $this->adGroupService->getAll(request()->all());

        return $this->paginatedResponse(AdGroupResource::collection($groups));
    }

    public function store(CreateAdGroupRequest $request)
    {
        $group = $this->adGroupService->create($request->validated());

        return $this->successResponse(AdGroupResource::make($group))->created('ad_group');
    }

    public function show($id)
    {
        $group = AdGroup::with(['creator', 'ads' => fn ($q) => $q->withTrashed()])
            ->findOrFail($id);

        return $this->successResponse(AdGroupResource::make($group));
    }

    public function update(UpdateAdGroupRequest $request, $id)
    {
        $group = $this->adGroupService->update((int) $id, $request->validated());

        return $this->successResponse(AdGroupResource::make($group))->updated('ad_group');
    }

    public function archive($id)
    {
        $this->adGroupService->archive((int) $id);

        return $this->successResponse()->deleted('ad_group');
    }

    public function restore($id)
    {
        $this->adGroupService->restore((int) $id);
        $group = AdGroup::with(['creator', 'ads' => fn ($q) => $q->withTrashed()])
            ->findOrFail($id);

        return $this->successResponse(AdGroupResource::make($group), __('crud.restored', ['model' => __('models.ad_group')]));
    }

    public function setDefault(SetDefaultAdRequest $request)
    {
        $validated = $request->validated();
        $ad = $this->adGroupService->setDefaultAd(
            (int) $validated['ad_group_id'],
            (int) $validated['ad_id']
        );

        return $this->successResponse(['ad_id' => $ad->id], __('messages.default_ad_set'));
    }

    public function removeDefault($groupId)
    {
        $this->adGroupService->removeDefaultAd((int) $groupId);

        return $this->successResponse([], __('messages.default_ad_removed'));
    }
}
