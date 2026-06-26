<?php

namespace Modules\Crm\Services;

use App\Services\BaseService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Crm\DTOs\ChangeLeadStatusDTO;
use Modules\Crm\DTOs\LeadDTO;
use Modules\Crm\Entities\Lead;
use Modules\Crm\Enums\LeadStatus;

class LeadService extends BaseService
{
    protected const CACHE_TAG = 'crm_leads';

    protected const ARCHIVE_RESTORE_DAYS = 30;

    public function __construct(
        protected readonly LeadStatsService $statsService
    ) {}

    public function list(): LengthAwarePaginator
    {
        return Lead::query()
            ->where('trader_id', auth()->id())
            ->active()
            ->with(['notes', 'followUps'])
            ->filter()
            ->recentlyActive()
            ->paginate($this->getPerPage());
    }

    public function archived(): LengthAwarePaginator
    {
        return Lead::query()
            ->where('trader_id', auth()->id())
            ->archived()
            ->filter()
            ->orderByDesc('archived_at')
            ->paginate($this->getPerPage());
    }

    public function find(int $id): Lead
    {
        $lead = Lead::with(['notes', 'followUps', 'trader'])->findOrFail($id);
        abort_unless($lead->isOwnedBy(auth()->id()), 403, 'unauthorized_lead_access');

        return $lead;
    }

    public function store(LeadDTO $dto): Lead
    {
        $lead = DB::transaction(function () use ($dto) {
            $lead = Lead::create($dto->toArray() + [
                'status' => LeadStatus::NEW,
                'status_changed_at' => now(),
                'last_activity_at' => now(),
            ]);
            $lead->touchActivity();

            return $lead;
        });
        $this->clearCache();
        $this->statsService->clearCache();

        return $lead;
    }

    public function update(Lead $lead, LeadDTO $dto): Lead
    {
        abort_unless($lead->isOwnedBy(auth()->id()), 403, 'unauthorized_lead_access');
        DB::transaction(function () use ($lead, $dto) {
            $lead->update($dto->toArray());
            $lead->touchActivity();
        });
        $this->clearCache();
        $this->statsService->clearCache();

        return $lead->fresh();
    }

    public function changeStatus(Lead $lead, ChangeLeadStatusDTO $dto): Lead
    {
        abort_unless($lead->isOwnedBy(auth()->id()), 403, 'unauthorized_lead_access');

        if ($dto->status === LeadStatus::LOST->value && empty($dto->lost_reason)) {
            throw new \InvalidArgumentException('lost_reason_required');
        }

        return DB::transaction(function () use ($lead, $dto) {
            $lead->update([
                'status' => $dto->status,
                'lost_reason' => $dto->lost_reason,
                'status_changed_at' => now(),
            ]);
            $lead->touchActivity();
            $this->clearCache();
            $this->statsService->clearCache();

            return $lead->fresh();
        });
    }

    public function archive(Lead $lead): Lead
    {
        abort_unless($lead->isOwnedBy(auth()->id()), 403, 'unauthorized_lead_access');
        $lead->update(['archived_at' => now(), 'last_activity_at' => now()]);
        $this->clearCache();
        $this->statsService->clearCache();

        return $lead;
    }

    public function restore(int $id): Lead
    {
        $lead = Lead::onlyTrashed()->findOrFail($id);
        abort_unless($lead->isOwnedBy(auth()->id()), 403, 'unauthorized_lead_access');

        if ($lead->archived_at && $lead->archived_at->lt(now()->subDays(static::ARCHIVE_RESTORE_DAYS))) {
            throw new \RuntimeException('archive_period_expired');
        }

        $lead->restore();
        $lead->update(['archived_at' => null, 'last_activity_at' => now()]);
        $this->clearCache();
        $this->statsService->clearCache();

        return $lead;
    }

    public function checkDuplicatePhone(string $phone): bool
    {
        return Lead::where('trader_id', auth()->id())
            ->where('phone', $phone)
            ->exists();
    }
}
