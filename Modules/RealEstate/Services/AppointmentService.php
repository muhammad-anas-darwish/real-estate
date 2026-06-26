<?php

namespace Modules\RealEstate\Services;

use App\Services\BaseService;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\RealEstate\DTOs\AppointmentDTO;
use Modules\RealEstate\DTOs\LeadFollowUpDTO;
use Modules\RealEstate\Entities\Appointment;
use Modules\RealEstate\Entities\Property;
use Modules\RealEstate\Enums\AppointmentType;
use Modules\RealEstate\Enums\ViewingStatus;

class AppointmentService extends BaseService
{
    protected const CACHE_TAG = 'appointments';

    protected const MIN_NOTICE_MINUTES = 60;

    protected const MAX_ADVANCE_DAYS = 30;

    protected const MAX_VIEWINGS_PER_DAY = 12;

    protected const MAX_VIEWINGS_PER_PROPERTY_DAY = 6;

    public function list(?int $agentId = null): LengthAwarePaginator
    {
        $query = Appointment::query()
            ->filter()
            ->with(['property', 'user', 'agent', 'cancelledBy', 'followable']);

        if ($agentId) {
            $query->forAgent($agentId);
        }

        if (request('date')) {
            $query->whereDate('scheduled_at', request('date'));
        }

        return $query
            ->orderBy(request('sort_by', 'scheduled_at'), request('sort_order', 'asc'))
            ->paginate($this->getPerPage());
    }

    public function myAppointments(): LengthAwarePaginator
    {
        return Appointment::query()
            ->where('user_id', Auth::id())
            ->filter()
            ->with(['property', 'agent', 'cancelledBy', 'followable'])
            ->orderBy('scheduled_at', 'desc')
            ->paginate($this->getPerPage());
    }

    public function agentSchedule(int $agentId): LengthAwarePaginator
    {
        return Appointment::query()
            ->forAgent($agentId)
            ->upcoming()
            ->filter()
            ->with(['property', 'user', 'cancelledBy', 'followable'])
            ->orderBy('scheduled_at', 'asc')
            ->paginate($this->getPerPage());
    }

    public function find(int $id): Appointment
    {
        return Appointment::with(['property', 'user', 'agent', 'cancelledBy', 'followable'])->findOrFail($id);
    }

    public function schedule(AppointmentDTO $dto): Appointment
    {
        return DB::transaction(function () use ($dto) {
            $type = $dto->type ? AppointmentType::from($dto->type) : AppointmentType::VIEWING;

            if ($type === AppointmentType::VIEWING && ! $dto->property_id) {
                throw new \InvalidArgumentException(__('messages.appointment_viewing_requires_property'));
            }

            if ($type === AppointmentType::FOLLOW_UP && (! $dto->followable_id || ! $dto->followable_type)) {
                throw new \InvalidArgumentException(__('messages.appointment_follow_up_requires_followable'));
            }

            $scheduledAt = Carbon::parse($dto->scheduled_at);
            $duration = $dto->duration_minutes ?? 30;
            $buffer = $dto->buffer_minutes ?? 15;

            $agentId = $dto->agent_id;
            $propertyId = $dto->property_id;

            if ($type === AppointmentType::VIEWING) {
                $property = Property::findOrFail($propertyId);
                $agentId ??= $property->publisher_id;
                $this->validateSchedulingRules($agentId, $property->id, $scheduledAt, $duration, $buffer);
            } else {
                $agentId ??= Auth::id();
            }

            $appointment = Appointment::create([
                'type' => $type->value,
                'property_id' => $propertyId,
                'user_id' => $dto->user_id ?? Auth::id(),
                'agent_id' => $agentId,
                'scheduled_at' => $scheduledAt,
                'duration_minutes' => $duration,
                'buffer_minutes' => $buffer,
                'status' => ViewingStatus::PENDING,
                'viewing_type' => $dto->viewing_type ?? 'in_person',
                'contact_method' => $dto->contact_method,
                'contact_name' => $dto->contact_name,
                'contact_phone' => $dto->contact_phone,
                'notes' => $dto->notes,
                'max_attendees' => $dto->max_attendees,
                'followable_id' => $dto->followable_id,
                'followable_type' => $dto->followable_type,
            ]);

            $this->clearCache();

            return $appointment->load(['property', 'user', 'agent', 'followable']);
        });
    }

    public function scheduleFollowUp(LeadFollowUpDTO $dto): Appointment
    {
        return DB::transaction(function () use ($dto) {
            $scheduledAt = Carbon::parse($dto->scheduled_at);

            $appointment = Appointment::create([
                'type' => AppointmentType::FOLLOW_UP->value,
                'property_id' => null,
                'user_id' => Auth::id(),
                'agent_id' => $dto->agent_id,
                'scheduled_at' => $scheduledAt,
                'duration_minutes' => $dto->duration_minutes ?? 30,
                'buffer_minutes' => $dto->buffer_minutes ?? 15,
                'status' => ViewingStatus::PENDING,
                'contact_method' => $dto->contact_method,
                'contact_name' => $dto->contact_name,
                'contact_phone' => $dto->contact_phone,
                'notes' => $dto->notes,
                'followable_id' => $dto->followable_id,
                'followable_type' => $dto->followable_type,
            ]);

            $this->clearCache();

            return $appointment->load(['followable']);
        });
    }

    public function confirm(int $id): Appointment
    {
        return DB::transaction(function () use ($id) {
            $appointment = $this->find($id);

            if (! in_array($appointment->status, [ViewingStatus::PENDING, ViewingStatus::RESCHEDULED])) {
                throw new \RuntimeException(__('messages.appointment_cannot_be_confirmed'));
            }

            $appointment->update([
                'status' => ViewingStatus::CONFIRMED,
                'confirmed_at' => now(),
            ]);

            $this->clearCache();

            return $appointment->fresh(['property', 'user', 'agent', 'followable']);
        });
    }

    public function reschedule(int $id, string $newScheduledAt): Appointment
    {
        return DB::transaction(function () use ($id, $newScheduledAt) {
            $appointment = $this->find($id);

            if (in_array($appointment->status, [ViewingStatus::CANCELLED, ViewingStatus::COMPLETED, ViewingStatus::NO_SHOW])) {
                throw new \RuntimeException(__('messages.appointment_cannot_be_rescheduled'));
            }

            $scheduledAt = Carbon::parse($newScheduledAt);

            if ($appointment->property_id) {
                $this->validateSchedulingRules(
                    $appointment->agent_id,
                    $appointment->property_id,
                    $scheduledAt,
                    $appointment->duration_minutes,
                    $appointment->buffer_minutes,
                    $appointment->id
                );
            }

            $appointment->update([
                'scheduled_at' => $scheduledAt,
                'status' => ViewingStatus::RESCHEDULED,
            ]);

            $this->clearCache();

            return $appointment->fresh(['property', 'user', 'agent', 'followable']);
        });
    }

    public function cancel(int $id, ?string $reason = null): Appointment
    {
        return DB::transaction(function () use ($id, $reason) {
            $appointment = $this->find($id);

            if ($appointment->status === ViewingStatus::COMPLETED) {
                throw new \RuntimeException(__('messages.appointment_already_completed'));
            }

            $appointment->update([
                'status' => ViewingStatus::CANCELLED,
                'cancelled_by' => Auth::id(),
                'cancellation_reason' => $reason,
                'cancelled_at' => now(),
            ]);

            $this->clearCache();

            return $appointment->fresh(['property', 'user', 'agent', 'cancelledBy', 'followable']);
        });
    }

    public function complete(int $id, ?string $agentNotes = null): Appointment
    {
        return DB::transaction(function () use ($id, $agentNotes) {
            $appointment = $this->find($id);

            if ($appointment->status !== ViewingStatus::CONFIRMED) {
                throw new \RuntimeException(__('messages.appointment_must_be_confirmed'));
            }

            $appointment->update([
                'status' => ViewingStatus::COMPLETED,
                'completed_at' => now(),
                'agent_notes' => $agentNotes ?? $appointment->agent_notes,
            ]);

            $this->clearCache();

            return $appointment->fresh(['property', 'user', 'agent', 'followable']);
        });
    }

    public function completeWithNote(int $id, ?string $note): Appointment
    {
        return $this->complete($id, $note);
    }

    public function markNoShow(int $id): Appointment
    {
        return DB::transaction(function () use ($id) {
            $appointment = $this->find($id);

            if (! in_array($appointment->status, [ViewingStatus::PENDING, ViewingStatus::CONFIRMED])) {
                throw new \RuntimeException(__('messages.appointment_cannot_mark_no_show'));
            }

            $appointment->update([
                'status' => ViewingStatus::NO_SHOW,
            ]);

            $this->clearCache();

            return $appointment->fresh(['property', 'user', 'agent', 'followable']);
        });
    }

    public function agentCalendar(int $agentId, string $from, string $to): Collection
    {
        $cacheKey = $this->generateCacheKey(compact('agentId', 'from', 'to'), 'calendar');

        return Cache::tags(static::CACHE_TAG)->remember($cacheKey, 300, function () use ($agentId, $from, $to) {
            return Appointment::forAgent($agentId)
                ->notCancelled()
                ->whereBetween('scheduled_at', [Carbon::parse($from)->startOfDay(), Carbon::parse($to)->endOfDay()])
                ->with(['property:id,name', 'followable'])
                ->orderBy('scheduled_at')
                ->get();
        });
    }

    public function dailyCount(int $agentId, ?string $date = null): int
    {
        $date = $date ? Carbon::parse($date) : today();

        return Appointment::forAgent($agentId)
            ->notCancelled()
            ->whereDate('scheduled_at', $date)
            ->count();
    }

    public function propertyDailyCount(int $propertyId, ?string $date = null): int
    {
        $date = $date ? Carbon::parse($date) : today();

        return Appointment::forProperty($propertyId)
            ->notCancelled()
            ->whereDate('scheduled_at', $date)
            ->count();
    }

    private function checkOverlap(
        int $id,
        string $column,
        Carbon $newStart,
        Carbon $newEnd,
        ?int $excludeAppointmentId = null
    ): bool {
        $conflictingIds = Appointment::where($column, $id)
            ->notCancelled()
            ->where('scheduled_at', '<', $newEnd)
            ->get()
            ->filter(function (Appointment $existing) use ($newStart, $excludeAppointmentId) {
                if ($excludeAppointmentId && $existing->id === $excludeAppointmentId) {
                    return false;
                }

                return $existing->ends_at->gt($newStart);
            })
            ->pluck('id');

        return $conflictingIds->isNotEmpty();
    }

    private function validateSchedulingRules(
        int $agentId,
        int $propertyId,
        Carbon $scheduledAt,
        int $durationMinutes,
        int $bufferMinutes,
        ?int $excludeAppointmentId = null
    ): void {
        $minTime = now()->addMinutes(static::MIN_NOTICE_MINUTES);
        $maxTime = now()->addDays(static::MAX_ADVANCE_DAYS);

        if ($scheduledAt->lt($minTime)) {
            throw new \RuntimeException(__('messages.appointment_min_notice', ['minutes' => static::MIN_NOTICE_MINUTES]));
        }

        if ($scheduledAt->gt($maxTime)) {
            throw new \RuntimeException(__('messages.appointment_max_advance', ['days' => static::MAX_ADVANCE_DAYS]));
        }

        $dailyCount = $this->dailyCount($agentId, $scheduledAt->toDateString());
        if ($dailyCount >= static::MAX_VIEWINGS_PER_DAY) {
            throw new \RuntimeException(__('messages.appointment_max_daily_reached'));
        }

        $propertyDailyCount = $this->propertyDailyCount($propertyId, $scheduledAt->toDateString());
        if ($propertyDailyCount >= static::MAX_VIEWINGS_PER_PROPERTY_DAY) {
            throw new \RuntimeException(__('messages.appointment_max_property_daily'));
        }

        $newEnd = $scheduledAt->copy()->addMinutes($durationMinutes + $bufferMinutes);

        $propertyOverlap = $this->checkOverlap($propertyId, 'property_id', $scheduledAt, $newEnd, $excludeAppointmentId);
        if ($propertyOverlap) {
            throw new \RuntimeException(__('messages.appointment_time_conflict'));
        }

        $agentOverlap = $this->checkOverlap($agentId, 'agent_id', $scheduledAt, $newEnd, $excludeAppointmentId);
        if ($agentOverlap) {
            throw new \RuntimeException(__('messages.appointment_agent_busy'));
        }
    }
}
