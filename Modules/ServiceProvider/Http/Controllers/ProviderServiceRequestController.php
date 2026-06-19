<?php

namespace Modules\ServiceProvider\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\ServiceProvider\Http\Requests\CompleteTaskRequest;
use Modules\ServiceProvider\Http\Resources\ServiceRequestResource;
use Modules\ServiceProvider\Services\ServiceRequestService;

class ProviderServiceRequestController extends Controller
{
    public function __construct(
        protected readonly ServiceRequestService $serviceRequestService
    ) {}

    public function index()
    {
        $profile = Auth::user()->serviceProviderProfile;

        if (! $profile) {
            return $this->failedResponse('You are not a service provider.', 403);
        }

        $requests = $this->serviceRequestService->listForProvider($profile->id);

        return $this->paginatedResponse(ServiceRequestResource::collection($requests));
    }

    public function show($id)
    {
        $profile = Auth::user()->serviceProviderProfile;

        if (! $profile) {
            return $this->failedResponse('You are not a service provider.', 403);
        }

        $serviceRequest = $this->serviceRequestService->find((int) $id);

        if ($serviceRequest->provider_id && $serviceRequest->provider_id !== $profile->id
            && $serviceRequest->status->value === 'pending') {
            return $this->failedResponse('Unauthorized', 403);
        }

        return $this->successResponse(ServiceRequestResource::make($serviceRequest));
    }

    public function accept($id)
    {
        $profile = Auth::user()->serviceProviderProfile;

        if (! $profile) {
            return $this->failedResponse('You are not a service provider.', 403);
        }

        $serviceRequest = $this->serviceRequestService->accept((int) $id, $profile->id);

        return $this->successResponse(
            ServiceRequestResource::make($serviceRequest),
            'Service request accepted'
        );
    }

    public function reject($id)
    {
        $profile = Auth::user()->serviceProviderProfile;

        if (! $profile) {
            return $this->failedResponse('You are not a service provider.', 403);
        }

        $serviceRequest = $this->serviceRequestService->reject((int) $id, $profile->id);

        return $this->successResponse(
            ServiceRequestResource::make($serviceRequest),
            'Service request rejected'
        );
    }

    public function startProgress($id)
    {
        $profile = Auth::user()->serviceProviderProfile;

        if (! $profile) {
            return $this->failedResponse('You are not a service provider.', 403);
        }

        $serviceRequest = $this->serviceRequestService->startProgress((int) $id, $profile->id);

        return $this->successResponse(
            ServiceRequestResource::make($serviceRequest),
            'Work started'
        );
    }

    public function complete($id)
    {
        $profile = Auth::user()->serviceProviderProfile;

        if (! $profile) {
            return $this->failedResponse('You are not a service provider.', 403);
        }

        $providerNotes = request('provider_notes');

        $serviceRequest = $this->serviceRequestService->complete((int) $id, $profile->id, $providerNotes);

        return $this->successResponse(
            ServiceRequestResource::make($serviceRequest),
            'Service request completed'
        );
    }

    public function completeTask($taskId, CompleteTaskRequest $request)
    {
        $task = $this->serviceRequestService->completeTask(
            (int) $taskId,
            $request->validated('checklist_json')
        );

        return $this->successResponse($task, 'Task completed');
    }
}
