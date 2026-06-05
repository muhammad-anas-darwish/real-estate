<?php

namespace Modules\RealEstate\Expert\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\RealEstate\Expert\Enums\ExpertType;
use Modules\RealEstate\Expert\Http\Requests\StoreExpertRequestRequest;
use Modules\RealEstate\Expert\Http\Resources\ExpertRequestResource;
use Modules\RealEstate\Expert\Services\ExpertRequestService;
use Illuminate\Http\Request;

class ExpertRequestController extends Controller
{
    public function __construct(
        protected readonly ExpertRequestService $expertRequestService,
    ) {
        $this->applyPermissions(
            'expert_requests',
            ['index', 'show', 'store'],
            ['cancel']
        );
    }

    public function index(Request $request): JsonResponse
    {
        if ($request->user()->hasRole('super-admin') || $request->user()->hasRole('admin')) {
            $requests = $this->expertRequestService->getPendingRequests();
        } else {
            $requests = $this->expertRequestService->getUserRequests($request->user());
        }

        return $this->paginatedResponse($requests, 'Expert requests retrieved successfully');
    }

    public function store(StoreExpertRequestRequest $request): JsonResponse
    {
        try {
            $expertRequest = $this->expertRequestService->createRequest(
                $request->user(),
                ExpertType::from($request->validated('expert_type')),
                $request->validated('message')
            );

            return $this->successResponse(
                new ExpertRequestResource($expertRequest),
                'Expert request created successfully',
                201
            );
        } catch (\Exception $e) {
            return $this->failedResponse($e->getMessage(), 400);
        }
    }

    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $expertRequest = $this->expertRequestService->getRequest($id);

            if ($expertRequest->user_id !== $request->user()->id &&
                !$request->user()->hasRole('super-admin') &&
                !$request->user()->hasRole('admin')) {
                return $this->unauthorizedResponse();
            }

            return $this->successResponse(
                new ExpertRequestResource($expertRequest),
                'Expert request retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->notFoundResponse();
        }
    }

    public function cancel(Request $request, int $id): JsonResponse
    {
        try {
            $expertRequest = $this->expertRequestService->getRequest($id);

            if ($expertRequest->user_id !== $request->user()->id) {
                return $this->unauthorizedResponse();
            }

            $this->expertRequestService->cancelRequest($expertRequest, $request->user());

            return $this->successResponse(
                new ExpertRequestResource($expertRequest->fresh()),
                'Expert request cancelled successfully'
            );
        } catch (\Exception $e) {
            return $this->failedResponse($e->getMessage(), 400);
        }
    }
}
