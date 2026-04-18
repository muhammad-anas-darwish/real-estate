<?php

namespace App\Traits;

use App\Support\ResponseBuilder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;

trait ApiResponses
{
    /**
     * Send a success response with data
     */
    protected function successResponse(
        array|JsonResource|Model|Collection|SupportCollection $data = [],
        ?string $message = null,
        int $statusCode = 200
    ): ResponseBuilder {
        return new ResponseBuilder([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $statusCode);
    }

    /**
     * Send a paginated response
     */
    protected function paginatedResponse(
        LengthAwarePaginator|AnonymousResourceCollection $paginatedData,
        ?string $message = null,
        int $statusCode = 200
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $paginatedData->items(),
            'pagination' => [
                'total' => $paginatedData->total(),
                'per_page' => $paginatedData->perPage(),
                'current_page' => $paginatedData->currentPage(),
                'last_page' => $paginatedData->lastPage(),
                'from' => $paginatedData->firstItem(),
                'to' => $paginatedData->lastItem()
            ]
        ], $statusCode);
    }

    /**
     * Send an error response
     */
    protected function failedResponse(
        string $message,
        int $statusCode = 400,
        ?array $errors = null,
        array|JsonResource|Model|Collection|SupportCollection $data = null
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
            'data' => $data
        ], $statusCode);
    }

    /**
     * Send a "not found" response
     */
    protected function notFoundResponse(
        string $message = 'exceptions.not_found',
        array|JsonResource|Model|Collection|SupportCollection $data = null
    ): JsonResponse {
        return $this->failedResponse(__($message), 404, null, $data);
    }

    /**
     * Send an "unauthorized" response
     */
    protected function unauthorizedResponse(
        string $message = 'exceptions.unauthenticated',
        array|JsonResource|Model|Collection|SupportCollection $data = null
    ): JsonResponse {
        return $this->failedResponse(__($message), 401, null, $data);
    }

    /**
     * Send a "validation error" response
     */
    protected function validationErrorResponse(
        array $errors,
        string $message = 'exceptions.validation_failed'
    ): JsonResponse {
        return $this->failedResponse(__($message), 422, $errors);
    }

    protected function serverErrorResponse(): JsonResponse {
        return $this->failedResponse(__('exceptions.server_error'), 500);
    }
}
