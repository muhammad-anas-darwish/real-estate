<?php

namespace Modules\RealEstate\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Modules\RealEstate\DTOs\PropertyViewingDTO;
use Modules\RealEstate\Entities\Property;
use Modules\RealEstate\Http\Requests\BookViewingRequest;
use Modules\RealEstate\Http\Requests\UpdateViewingStatusRequest;
use Modules\RealEstate\Http\Requests\ViewingFilterRequest;
use Modules\RealEstate\Http\Resources\PropertyViewingResource;
use Modules\RealEstate\Services\PropertyViewingService;

class PropertyViewingController extends Controller
{
    public function __construct(
        protected readonly PropertyViewingService $viewingService
    ) {
        $this->applyPermissions(
            'viewings',
            ['index', 'show', 'store', 'destroy'],
            [
                'agentSchedule' => 'list',
                'myViewings' => 'list',
                'calendar' => 'list',
                'confirm' => 'edit',
                'cancel' => 'cancel',
                'reschedule' => 'edit',
                'complete' => 'edit',
                'markNoShow' => 'edit',
            ]
        );
    }

    public function index(ViewingFilterRequest $request)
    {
        $agentId = request('agent_id') ?? (Auth::user()?->hasPermissionTo('viewings.list') ? null : Auth::id());

        $viewings = $this->viewingService->list($agentId);

        return $this->paginatedResponse(PropertyViewingResource::collection($viewings));
    }

    public function show($id)
    {
        $viewing = $this->viewingService->find($id);

        return $this->successResponse(PropertyViewingResource::make($viewing));
    }

    public function store(BookViewingRequest $request)
    {
        $dto = PropertyViewingDTO::fromRequest($request->validated());

        if (! $dto->agent_id) {
            $property = Property::findOrFail($dto->property_id);
            $dto = PropertyViewingDTO::fromRequest(array_merge($request->validated(), [
                'agent_id' => $property->publisher_id,
            ]));
        }

        $viewing = $this->viewingService->schedule($dto);

        return $this->successResponse(
            PropertyViewingResource::make($viewing),
            __('messages.viewing_scheduled')
        )->created('viewing');
    }

    public function myViewings()
    {
        $viewings = $this->viewingService->myViewings();

        return $this->paginatedResponse(PropertyViewingResource::collection($viewings));
    }

    public function agentSchedule()
    {
        $viewings = $this->viewingService->agentSchedule(Auth::id());

        return $this->paginatedResponse(PropertyViewingResource::collection($viewings));
    }

    public function confirm($id)
    {
        $viewing = $this->viewingService->find($id);

        if (Gate::denies('confirm', $viewing)) {
            return $this->forbiddenResponse();
        }

        $viewing = $this->viewingService->confirm($id);

        return $this->successResponse(
            PropertyViewingResource::make($viewing),
            __('messages.viewing_confirmed')
        );
    }

    public function reschedule(UpdateViewingStatusRequest $request, $id)
    {
        $viewing = $this->viewingService->find($id);
        $validated = $request->validated();

        if (Gate::denies('reschedule', $viewing)) {
            return $this->forbiddenResponse();
        }

        $viewing = $this->viewingService->reschedule($id, $validated['scheduled_at']);

        return $this->successResponse(
            PropertyViewingResource::make($viewing),
            __('messages.viewing_rescheduled')
        );
    }

    public function cancel(UpdateViewingStatusRequest $request, $id)
    {
        $viewing = $this->viewingService->find($id);
        $validated = $request->validated();

        if (Gate::denies('cancel', $viewing)) {
            return $this->forbiddenResponse();
        }

        $viewing = $this->viewingService->cancel($id, $validated['cancellation_reason'] ?? null);

        return $this->successResponse(
            PropertyViewingResource::make($viewing),
            __('messages.viewing_cancelled')
        );
    }

    public function complete($id)
    {
        $viewing = $this->viewingService->find($id);

        if (Gate::denies('complete', $viewing)) {
            return $this->forbiddenResponse();
        }

        $viewing = $this->viewingService->complete($id);

        return $this->successResponse(
            PropertyViewingResource::make($viewing),
            __('messages.viewing_completed')
        );
    }

    public function markNoShow($id)
    {
        $viewing = $this->viewingService->find($id);

        if (Gate::denies('markNoShow', $viewing)) {
            return $this->forbiddenResponse();
        }

        $viewing = $this->viewingService->markNoShow($id);

        return $this->successResponse(
            PropertyViewingResource::make($viewing),
            __('messages.viewing_no_show')
        );
    }

    public function destroy($id)
    {
        $viewing = $this->viewingService->find($id);

        if (Gate::denies('delete', $viewing)) {
            return $this->forbiddenResponse();
        }

        $viewing->delete();

        return $this->successResponse()->deleted('viewing');
    }

    public function calendar(ViewingFilterRequest $request)
    {
        $from = request('from', now()->startOfWeek()->toDateString());
        $to = request('to', now()->endOfWeek()->toDateString());

        $viewings = $this->viewingService->agentCalendar(Auth::id(), $from, $to);

        return $this->successResponse(PropertyViewingResource::collection($viewings));
    }
}
