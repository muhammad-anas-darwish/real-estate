<?php

namespace Modules\RealEstate\Services;

use App\Services\BaseService;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\RealEstate\DTOs\PropertyViewingDTO;
use Modules\RealEstate\Entities\Property;
use Modules\RealEstate\Entities\PropertyViewing;
use Modules\RealEstate\Enums\ViewingStatus;

class PropertyViewingService extends BaseService
{
    protected const CACHE_TAG = 'property_viewings';

    protected const MIN_NOTICE_MINUTES = 60;

    protected const MAX_ADVANCE_DAYS = 30;

    protected const MAX_VIEWINGS_PER_DAY = 12;

    protected const MAX_VIEWINGS_PER_PROPERTY_DAY = 6;

    public function list(?int $agentId = null): LengthAwarePaginator
    {
        $query = PropertyViewing::query()
            ->filter()
            ->with(['property', 'user', 'agent', 'cancelledBy']);

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

    public function myViewings(): LengthAwarePaginator
    {
        return PropertyViewing::query()
            ->where('user_id', Auth::id())
            ->filter()
            ->with(['property', 'agent', 'cancelledBy'])
            ->orderBy('scheduled_at', 'desc')
            ->paginate($this->getPerPage());
    }

    public function agentSchedule(int $agentId): LengthAwarePaginator
    {
        return PropertyViewing::query()
            ->forAgent($agentId)
            ->upcoming()
            ->filter()
            ->with(['property', 'user', 'cancelledBy'])
            ->orderBy('scheduled_at', 'asc')
            ->paginate($this->getPerPage());
    }

    public function find(int $id): PropertyViewing
    {
        return PropertyViewing::with(['property', 'user', 'agent', 'cancelledBy'])->findOrFail($id);
    }

    public function schedule(PropertyViewingDTO $dto): PropertyViewing
    {
        return DB::transaction(function () use ($dto) {
            $property = Property::findOrFail($dto->property_id);
            $agentId = $dto->agent_id ?? $property->publisher_id;
            $scheduledAt = Carbon::parse($dto->scheduled_at);
            $duration = $dto->duration_minutes ?? 30;
            $buffer = $dto->buffer_minutes ?? 15;

            $this->validateSchedulingRules($agentId, $property->id, $scheduledAt, $duration, $buffer);

            $viewing = PropertyViewing::create([
                'property_id' => $property->id,
                'user_id' => $dto->user_id ?? Auth::id(),
                'agent_id' => $agentId,
                'scheduled_at' => $scheduledAt,
                'duration_minutes' => $duration,
                'buffer_minutes' => $buffer,
                'status' => ViewingStatus::PENDING,
                'viewing_type' => $dto->viewing_type ?? 'in_person',
                'contact_name' => $dto->contact_name,
                'contact_phone' => $dto->contact_phone,
                'notes' => $dto->notes,
                'max_attendees' => $dto->max_attendees,
            ]);

            $this->clearCache();

            return $viewing->load(['property', 'user', 'agent']);
        });
    }

    public function confirm(int $id): PropertyViewing
    {
        return DB::transaction(function () use ($id) {
            $viewing = $this->find($id);

            if (! in_array($viewing->status, [ViewingStatus::PENDING, ViewingStatus::RESCHEDULED])) {
                throw new \RuntimeException(__('messages.viewing_cannot_be_confirmed'));
            }

            $viewing->update([
                'status' => ViewingStatus::CONFIRMED,
                'confirmed_at' => now(),
            ]);

            $this->clearCache();

            return $viewing->fresh(['property', 'user', 'agent']);
        });
    }

    public function reschedule(int $id, string $newScheduledAt): PropertyViewing
    {
        return DB::transaction(function () use ($id, $newScheduledAt) {
            $viewing = $this->find($id);

            if (in_array($viewing->status, [ViewingStatus::CANCELLED, ViewingStatus::COMPLETED, ViewingStatus::NO_SHOW])) {
                throw new \RuntimeException(__('messages.viewing_cannot_be_rescheduled'));
            }

            $scheduledAt = Carbon::parse($newScheduledAt);
            $this->validateSchedulingRules(
                $viewing->agent_id,
                $viewing->property_id,
                $scheduledAt,
                $viewing->duration_minutes,
                $viewing->buffer_minutes,
                $viewing->id
            );

            $viewing->update([
                'scheduled_at' => $scheduledAt,
                'status' => ViewingStatus::RESCHEDULED,
            ]);

            $this->clearCache();

            return $viewing->fresh(['property', 'user', 'agent']);
        });
    }

    public function cancel(int $id, ?string $reason = null): PropertyViewing
    {
        return DB::transaction(function () use ($id, $reason) {
            $viewing = $this->find($id);

            if ($viewing->status === ViewingStatus::COMPLETED) {
                throw new \RuntimeException(__('messages.viewing_already_completed'));
            }

            $viewing->update([
                'status' => ViewingStatus::CANCELLED,
                'cancelled_by' => Auth::id(),
                'cancellation_reason' => $reason,
                'cancelled_at' => now(),
            ]);

            $this->clearCache();

            return $viewing->fresh(['property', 'user', 'agent', 'cancelledBy']);
        });
    }

    public function complete(int $id, ?string $agentNotes = null): PropertyViewing
    {
        return DB::transaction(function () use ($id, $agentNotes) {
            $viewing = $this->find($id);

            if ($viewing->status !== ViewingStatus::CONFIRMED) {
                throw new \RuntimeException(__('messages.viewing_must_be_confirmed'));
            }

            $viewing->update([
                'status' => ViewingStatus::COMPLETED,
                'completed_at' => now(),
                'agent_notes' => $agentNotes ?? $viewing->agent_notes,
            ]);

            $this->clearCache();

            return $viewing->fresh(['property', 'user', 'agent']);
        });
    }

    public function markNoShow(int $id): PropertyViewing
    {
        return DB::transaction(function () use ($id) {
            $viewing = $this->find($id);

            if (! in_array($viewing->status, [ViewingStatus::PENDING, ViewingStatus::CONFIRMED])) {
                throw new \RuntimeException(__('messages.viewing_cannot_mark_no_show'));
            }

            $viewing->update([
                'status' => ViewingStatus::NO_SHOW,
            ]);

            $this->clearCache();

            return $viewing->fresh(['property', 'user', 'agent']);
        });
    }

    public function agentCalendar(int $agentId, string $from, string $to): Collection
    {
        $cacheKey = $this->generateCacheKey(compact('agentId', 'from', 'to'), 'calendar');

        return Cache::tags(static::CACHE_TAG)->remember($cacheKey, 300, function () use ($agentId, $from, $to) {
            return PropertyViewing::forAgent($agentId)
                ->notCancelled()
                ->whereBetween('scheduled_at', [Carbon::parse($from)->startOfDay(), Carbon::parse($to)->endOfDay()])
                ->with(['property:id,name'])
                ->orderBy('scheduled_at')
                ->get();
        });
    }

    public function dailyCount(int $agentId, ?string $date = null): int
    {
        $date = $date ? Carbon::parse($date) : today();

        return PropertyViewing::forAgent($agentId)
            ->notCancelled()
            ->whereDate('scheduled_at', $date)
            ->count();
    }

    public function propertyDailyCount(int $propertyId, ?string $date = null): int
    {
        $date = $date ? Carbon::parse($date) : today();

        return PropertyViewing::forProperty($propertyId)
            ->notCancelled()
            ->whereDate('scheduled_at', $date)
            ->count();
    }

    private function checkOverlap(
        int $id,
        string $column,
        Carbon $newStart,
        Carbon $newEnd,
        ?int $excludeViewingId = null
    ): bool {
        $conflictingIds = PropertyViewing::where($column, $id)
            ->notCancelled()
            ->where('scheduled_at', '<', $newEnd)
            ->get()
            ->filter(function (PropertyViewing $existing) use ($newStart, $excludeViewingId) {
                if ($excludeViewingId && $existing->id === $excludeViewingId) {
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
        ?int $excludeViewingId = null
    ): void {
        $minTime = now()->addMinutes(static::MIN_NOTICE_MINUTES);
        $maxTime = now()->addDays(static::MAX_ADVANCE_DAYS);

        if ($scheduledAt->lt($minTime)) {
            throw new \RuntimeException(__('messages.viewing_min_notice', ['minutes' => static::MIN_NOTICE_MINUTES]));
        }

        if ($scheduledAt->gt($maxTime)) {
            throw new \RuntimeException(__('messages.viewing_max_advance', ['days' => static::MAX_ADVANCE_DAYS]));
        }

        $dailyCount = $this->dailyCount($agentId, $scheduledAt->toDateString());
        if ($dailyCount >= static::MAX_VIEWINGS_PER_DAY) {
            throw new \RuntimeException(__('messages.viewing_max_daily_reached'));
        }

        $propertyDailyCount = $this->propertyDailyCount($propertyId, $scheduledAt->toDateString());
        if ($propertyDailyCount >= static::MAX_VIEWINGS_PER_PROPERTY_DAY) {
            throw new \RuntimeException(__('messages.viewing_max_property_daily'));
        }

        $newEnd = $scheduledAt->copy()->addMinutes($durationMinutes + $bufferMinutes);

        $propertyOverlap = $this->checkOverlap($propertyId, 'property_id', $scheduledAt, $newEnd, $excludeViewingId);
        if ($propertyOverlap) {
            throw new \RuntimeException(__('messages.viewing_time_conflict'));
        }

        $agentOverlap = $this->checkOverlap($agentId, 'agent_id', $scheduledAt, $newEnd, $excludeViewingId);
        if ($agentOverlap) {
            throw new \RuntimeException(__('messages.viewing_agent_busy'));
        }
    }
}
