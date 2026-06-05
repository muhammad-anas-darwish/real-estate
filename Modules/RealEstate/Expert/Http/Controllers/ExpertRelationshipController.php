<?php

namespace Modules\RealEstate\Expert\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\RealEstate\Expert\Http\Resources\ExpertRelationshipResource;
use Modules\RealEstate\Expert\Services\ExpertRelationshipService;

class ExpertRelationshipController extends Controller
{
    public function __construct(
        protected readonly ExpertRelationshipService $expertRelationshipService,
    ) {
        $this->applyPermissions(
            'expert_relationships',
            ['index', 'show'],
            ['cancel', 'complete']
        );
    }

    public function index(Request $request): JsonResponse
    {
        try {
            if ($request->user()->is_expert) {
                $relationships = $this->expertRelationshipService->getExpertRelationships($request->user());
            } else {
                $relationships = $this->expertRelationshipService->getUserRelationships($request->user());
            }

            return $this->paginatedResponse($relationships, 'Expert relationships retrieved successfully');
        } catch (\Exception $e) {
            return $this->failedResponse($e->getMessage(), 400);
        }
    }

    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $relationship = $this->expertRelationshipService->getRelationship($id);

            if ($relationship->expert_id !== $request->user()->id &&
                $relationship->user_id !== $request->user()->id) {
                return $this->unauthorizedResponse();
            }

            return $this->successResponse(
                new ExpertRelationshipResource($relationship),
                'Expert relationship retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->notFoundResponse();
        }
    }

    public function cancel(Request $request, int $id): JsonResponse
    {
        try {
            $relationship = $this->expertRelationshipService->getRelationship($id);

            if ($relationship->expert_id !== $request->user()->id &&
                $relationship->user_id !== $request->user()->id) {
                return $this->unauthorizedResponse();
            }

            $this->expertRelationshipService->cancelRelationship($relationship, $request->user());

            return $this->successResponse(
                new ExpertRelationshipResource($relationship->fresh()),
                'Expert relationship cancelled successfully'
            );
        } catch (\Exception $e) {
            return $this->failedResponse($e->getMessage(), 400);
        }
    }

    public function complete(Request $request, int $id): JsonResponse
    {
        try {
            $relationship = $this->expertRelationshipService->getRelationship($id);

            if ($relationship->expert_id !== $request->user()->id) {
                return $this->unauthorizedResponse();
            }

            $this->expertRelationshipService->completeRelationship($relationship, $request->user());

            return $this->successResponse(
                new ExpertRelationshipResource($relationship->fresh()),
                'Expert relationship completed successfully'
            );
        } catch (\Exception $e) {
            return $this->failedResponse($e->getMessage(), 400);
        }
    }
}
