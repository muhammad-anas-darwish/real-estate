<?php

namespace Modules\RealEstate\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Auth\Entities\User;
use Modules\RealEstate\DTOs\CreateSponsoredAdDTO;
use Modules\RealEstate\Entities\Ad;
use Modules\RealEstate\Http\Requests\CreateSponsoredAdRequest;
use Modules\RealEstate\Http\Resources\AdResource;
use Modules\RealEstate\Services\SponsoredAdService;

class SponsoredAdController extends Controller
{
    public function __construct(
        protected readonly SponsoredAdService $sponsoredAdService
    ) {
        $this->applyPermissions(
            'sponsored_ads',
            ['store', 'cancel'],
            [
                'index' => 'list_own',
                'show' => 'show_own',
                'pricing' => 'list',
            ]
        );
    }

    public function index()
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return $this->failedResponse('Unauthenticated', 401);
        }

        $ads = $this->sponsoredAdService->getForUser($user->id);

        return $this->paginatedResponse(AdResource::collection($ads), 'Sponsored ads');
    }

    public function store(CreateSponsoredAdRequest $request)
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return $this->failedResponse('Unauthenticated', 401);
        }

        $dto = CreateSponsoredAdDTO::fromRequest($request);

        try {
            $ad = $this->sponsoredAdService->create($dto, $user);
        } catch (\RuntimeException $e) {
            return $this->failedResponse($e->getMessage(), 422);
        }

        return $this->successResponse(AdResource::make($ad))
            ->created('sponsored_ad');
    }

    public function show($id)
    {
        $user = Auth::user();

        $ad = Ad::where('type', 'sponsored')
            ->where('user_id', $user->id)
            ->with(['user', 'media', 'property'])
            ->findOrFail($id);

        return $this->successResponse(AdResource::make($ad));
    }

    public function cancel($id)
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return $this->failedResponse('Unauthenticated', 401);
        }

        $ad = Ad::where('type', 'sponsored')
            ->where('user_id', $user->id)
            ->findOrFail($id);

        try {
            $ad = $this->sponsoredAdService->cancel($ad, $user);
        } catch (\RuntimeException $e) {
            return $this->failedResponse($e->getMessage(), 422);
        }

        return $this->successResponse(AdResource::make($ad), 'Sponsored ad cancelled');
    }

    public function pricing()
    {
        return $this->successResponse(
            $this->sponsoredAdService->getPricing(),
            'Sponsored ad pricing tiers'
        );
    }

    public function active()
    {
        $ads = Ad::activeSponsored()
            ->with(['user', 'media', 'property'])
            ->highestRotationWeight()
            ->orderByDesc('created_at')
            ->paginate($this->getPerPage());

        return $this->paginatedResponse(AdResource::collection($ads), 'Active sponsored ads');
    }
}
