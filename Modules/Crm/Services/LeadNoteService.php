<?php

namespace Modules\Crm\Services;

use App\Services\BaseService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Crm\DTOs\LeadNoteDTO;
use Modules\Crm\Entities\Lead;
use Modules\Crm\Entities\LeadNote;

class LeadNoteService extends BaseService
{
    protected const CACHE_TAG = 'crm_lead_notes';

    public function listForLead(Lead $lead): Collection
    {
        abort_unless($lead->isOwnedBy(auth()->id()), 403, 'unauthorized_lead_access');

        return $lead->notes()
            ->with('author:id,name')
            ->orderByDesc('created_at')
            ->get();
    }

    public function store(Lead $lead, LeadNoteDTO $dto): LeadNote
    {
        abort_unless($lead->isOwnedBy(auth()->id()), 403, 'unauthorized_lead_access');

        if (trim($dto->body) === '') {
            throw new \InvalidArgumentException('note_body_empty');
        }

        $note = DB::transaction(function () use ($lead, $dto) {
            $note = $lead->notes()->create($dto->toArray());
            $lead->touchActivity();

            return $note;
        });
        $this->clearCache();

        return $note;
    }

    public function update(LeadNote $note, string $body): LeadNote
    {
        if ($note->author_id !== auth()->id()) {
            throw new \RuntimeException('note_edit_unauthorized');
        }

        if (! $note->isEditableBy(auth()->user())) {
            throw new \RuntimeException('note_locked_for_editing');
        }

        if (trim($body) === '') {
            throw new \InvalidArgumentException('note_body_empty');
        }

        DB::transaction(function () use ($note, $body) {
            $note->update([
                'body' => $body,
                'is_locked' => $note->created_at->lte(now()->subHours(24)),
            ]);
            $note->lead->touchActivity();
        });
        $this->clearCache();

        return $note->fresh();
    }

    public function delete(LeadNote $note): void
    {
        if (! $note->isDeletableBy(auth()->user())) {
            throw new \RuntimeException('note_delete_unauthorized');
        }

        DB::transaction(function () use ($note) {
            $note->delete();
            $note->lead->touchActivity();
        });
        $this->clearCache();
    }
}
