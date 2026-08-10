<?php

namespace Modules\RealEstate\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Modules\RealEstate\DTOs\AppointmentDTO;
use Modules\RealEstate\DTOs\LeadFollowUpDTO;
use Modules\RealEstate\Entities\Property;
use Modules\RealEstate\Http\Requests\BookAppointmentRequest;
use Modules\RealEstate\Http\Requests\ScheduleFollowUpRequest;
use Modules\RealEstate\Http\Requests\UpdateAppointmentStatusRequest;
use Modules\RealEstate\Http\Requests\ViewingFilterRequest;
use Modules\RealEstate\Http\Resources\AppointmentResource;
use Modules\RealEstate\Services\AppointmentService;

class AppointmentController extends Controller
{
    public function __construct(
        protected readonly AppointmentService $appointmentService
    ) {
        $this->applyPermissions(
            'appointments',
            ['index', 'show', 'store', 'destroy'],
            [
                'agentSchedule' => 'list',
                'myAppointments' => 'list',
                'calendar' => 'list',
                'confirm' => 'edit',
                'cancel' => 'cancel',
                'reschedule' => 'edit',
                'complete' => 'edit',
                'markNoShow' => 'edit',
                'storeFollowUp' => 'create',
            ]
        );
    }

    public function index(ViewingFilterRequest $request)
    {
        $agentId = request('agent_id') ?? (Auth::user()?->hasPermissionTo('appointments.list') || Auth::user()?->hasPermissionTo('viewings.list') ? null : Auth::id());

        $appointments = $this->appointmentService->list($agentId);

        return $this->paginatedResponse(AppointmentResource::collection($appointments));
    }

    public function show($id)
    {
        $appointment = $this->appointmentService->find($id);

        return $this->successResponse(AppointmentResource::make($appointment));
    }

    public function store(BookAppointmentRequest $request)
    {
        $validated = $request->validated();
        $dto = AppointmentDTO::fromRequest($validated);

        $type = $dto->type ?? 'viewing';

        if ($type === 'viewing' && $dto->property_id && ! $dto->agent_id) {
            $property = Property::findOrFail($dto->property_id);
            $dto = AppointmentDTO::fromRequest(array_merge($validated, [
                'agent_id' => $property->publisher_id,
            ]));
        }

        $appointment = $this->appointmentService->schedule($dto);

        return $this->successResponse(
            AppointmentResource::make($appointment),
            __('messages.appointment_scheduled')
        )->created('appointment');
    }

    public function storeFollowUp(ScheduleFollowUpRequest $request)
    {
        $dto = LeadFollowUpDTO::fromRequest($request->validated());

        $appointment = $this->appointmentService->scheduleFollowUp($dto);

        return $this->successResponse(
            AppointmentResource::make($appointment),
            __('messages.appointment_follow_up_scheduled')
        )->created('appointment');
    }

    public function myAppointments()
    {
        $appointments = $this->appointmentService->myAppointments();

        return $this->paginatedResponse(AppointmentResource::collection($appointments));
    }

    public function agentSchedule()
    {
        $appointments = $this->appointmentService->agentSchedule(Auth::id());

        return $this->paginatedResponse(AppointmentResource::collection($appointments));
    }

    public function confirm($id)
    {
        $appointment = $this->appointmentService->find($id);

        if (Gate::denies('confirm', $appointment)) {
            return $this->forbiddenResponse();
        }

        $appointment = $this->appointmentService->confirm($id);

        return $this->successResponse(
            AppointmentResource::make($appointment),
            __('messages.appointment_confirmed')
        );
    }

    public function reschedule(UpdateAppointmentStatusRequest $request, $id)
    {
        $appointment = $this->appointmentService->find($id);
        $validated = $request->validated();

        if (Gate::denies('reschedule', $appointment)) {
            return $this->forbiddenResponse();
        }

        $appointment = $this->appointmentService->reschedule($id, $validated['scheduled_at']);

        return $this->successResponse(
            AppointmentResource::make($appointment),
            __('messages.appointment_rescheduled')
        );
    }

    public function cancel(UpdateAppointmentStatusRequest $request, $id)
    {
        $appointment = $this->appointmentService->find($id);
        $validated = $request->validated();

        if (Gate::denies('cancel', $appointment)) {
            return $this->forbiddenResponse();
        }

        $appointment = $this->appointmentService->cancel($id, $validated['cancellation_reason'] ?? null);

        return $this->successResponse(
            AppointmentResource::make($appointment),
            __('messages.appointment_cancelled')
        );
    }

    public function complete($id)
    {
        $appointment = $this->appointmentService->find($id);

        if (Gate::denies('complete', $appointment)) {
            return $this->forbiddenResponse();
        }

        $appointment = $this->appointmentService->complete($id);

        return $this->successResponse(
            AppointmentResource::make($appointment),
            __('messages.appointment_completed')
        );
    }

    public function markNoShow($id)
    {
        $appointment = $this->appointmentService->find($id);

        if (Gate::denies('markNoShow', $appointment)) {
            return $this->forbiddenResponse();
        }

        $appointment = $this->appointmentService->markNoShow($id);

        return $this->successResponse(
            AppointmentResource::make($appointment),
            __('messages.appointment_no_show')
        );
    }

    public function destroy($id)
    {
        $appointment = $this->appointmentService->find($id);

        if (Gate::denies('delete', $appointment)) {
            return $this->forbiddenResponse();
        }

        $appointment->delete();

        return $this->successResponse()->deleted('appointment');
    }

    public function calendar(ViewingFilterRequest $request)
    {
        $from = request('from', now()->startOfWeek()->toDateString());
        $to = request('to', now()->endOfWeek()->toDateString());

        $appointments = $this->appointmentService->agentCalendar(Auth::id(), $from, $to);

        return $this->successResponse(AppointmentResource::collection($appointments));
    }
}
