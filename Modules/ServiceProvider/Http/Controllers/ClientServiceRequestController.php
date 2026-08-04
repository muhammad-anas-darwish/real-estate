<?php

namespace Modules\ServiceProvider\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\ServiceProvider\DTOs\ServiceRequestDTO;
use Modules\ServiceProvider\Http\Requests\CreateServiceRequestRequest;
use Modules\ServiceProvider\Http\Resources\ServiceRequestResource;
use Modules\ServiceProvider\Services\ServiceRequestService;

class ClientServiceRequestController extends Controller
{
    public function __construct(
        protected readonly ServiceRequestService $serviceRequestService
    ) {}

    public function index()
    {
        $requests = $this->serviceRequestService->listForClient(Auth::id());

        return $this->paginatedResponse(ServiceRequestResource::collection($requests));
    }

    public function store(CreateServiceRequestRequest $request)
    {
        $dto = ServiceRequestDTO::fromRequest($request->validated());
        $serviceRequest = $this->serviceRequestService->create(Auth::id(), $dto);

        return $this->successResponse(
            ServiceRequestResource::make($serviceRequest),
            'Service request created successfully'
        )->created('service_request');
    }

    public function show($id)
    {
        $serviceRequest = $this->serviceRequestService->find((int) $id);

        if ($serviceRequest->client_id !== Auth::id()) {
            return $this->failedResponse('Unauthorized', 403);
        }

        return $this->successResponse(ServiceRequestResource::make($serviceRequest));
    }

    public function cancel($id)
    {
        $serviceRequest = $this->serviceRequestService->cancel((int) $id, Auth::id());

        return $this->successResponse(
            ServiceRequestResource::make($serviceRequest),
            'Service request cancelled'
        );
    }
}
