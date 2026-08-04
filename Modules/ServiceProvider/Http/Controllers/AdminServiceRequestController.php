<?php

namespace Modules\ServiceProvider\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\ServiceProvider\Http\Resources\ServiceRequestResource;
use Modules\ServiceProvider\Services\ServiceRequestService;

class AdminServiceRequestController extends Controller
{
    public function __construct(
        protected readonly ServiceRequestService $serviceRequestService
    ) {
        $this->applyPermissions(
            'service_requests',
            ['index', 'show'],
            [],
            ['manage' => ['show']]
        );
    }

    public function index()
    {
        $requests = $this->serviceRequestService->listAllForAdmin();

        return $this->paginatedResponse(ServiceRequestResource::collection($requests));
    }

    public function show($id)
    {
        $serviceRequest = $this->serviceRequestService->find((int) $id);

        return $this->successResponse(ServiceRequestResource::make($serviceRequest));
    }
}
