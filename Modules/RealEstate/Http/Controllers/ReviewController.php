<?php

namespace Modules\RealEstate\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\RealEstate\DTOs\ReviewDTO;
use Modules\RealEstate\Http\Resources\ReviewResource;
use Modules\RealEstate\Services\ReviewService;

class ReviewController extends Controller
{
    public function __construct(
        protected readonly ReviewService $reviewService
    ) {
        $this->applyPermissions(
            'reviews',
            ['destroy'],
            ['listForOffice' => 'list', 'store' => 'create']
        );
    }

    public function listForOffice($officeId)
    {
        $reviews = $this->reviewService->listForOffice((int) $officeId);

        return $this->paginatedResponse(ReviewResource::collection($reviews));
    }

    public function store()
    {
        $dto = ReviewDTO::fromRequest(request()->all());

        try {
            $review = $this->reviewService->store(Auth::id(), $dto);

            return $this->successResponse(ReviewResource::make($review))->created('review');
        } catch (\RuntimeException $e) {
            return $this->failedResponse($e->getMessage(), 422);
        }
    }

    public function destroy($id)
    {
        try {
            $this->reviewService->destroy((int) $id, Auth::id());

            return $this->successResponse()->deleted('review');
        } catch (\RuntimeException $e) {
            return $this->failedResponse($e->getMessage(), 422);
        }
    }
}
