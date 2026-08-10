<?php

namespace Modules\Crm\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Crm\DTOs\LeadNoteDTO;
use Modules\Crm\Entities\LeadNote;
use Modules\Crm\Http\Requests\StoreLeadNoteRequest;
use Modules\Crm\Http\Requests\UpdateLeadNoteRequest;
use Modules\Crm\Http\Resources\LeadNoteResource;
use Modules\Crm\Services\LeadNoteService;
use Modules\Crm\Services\LeadService;

class LeadNoteController extends Controller
{
    public function __construct(
        protected readonly LeadService $leadService,
        protected readonly LeadNoteService $noteService,
    ) {
        $this->applyPermissions(
            'lead_notes',
            ['index', 'store', 'update', 'destroy']
        );
    }

    public function index($leadId)
    {
        $lead = $this->leadService->find($leadId);
        $notes = $this->noteService->listForLead($lead);

        return $this->successResponse(LeadNoteResource::collection($notes));
    }

    public function store(StoreLeadNoteRequest $request, $leadId)
    {
        $lead = $this->leadService->find($leadId);
        $dto = LeadNoteDTO::fromRequest($request->validated() + ['lead_id' => $lead->id]);

        try {
            $note = $this->noteService->store($lead, $dto);
        } catch (\InvalidArgumentException $e) {
            return $this->validationErrorResponse(['body' => [__('messages.note_body_empty')]]);
        }

        return $this->successResponse(LeadNoteResource::make($note->load('author')))->created('lead_note');
    }

    public function update(UpdateLeadNoteRequest $request, $noteId)
    {
        $note = LeadNote::findOrFail($noteId);

        try {
            $updated = $this->noteService->update($note, $request->validated()['body']);
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'note_edit_unauthorized') {
                return $this->forbiddenResponse(__('messages.note_edit_unauthorized'));
            }

            return $this->validationErrorResponse(['body' => [__('messages.note_locked_for_editing')]]);
        } catch (\InvalidArgumentException $e) {
            return $this->validationErrorResponse(['body' => [__('messages.note_body_empty')]]);
        }

        return $this->successResponse(LeadNoteResource::make($updated->load('author')), __('messages.lead_note_updated'))->updated('lead_note');
    }

    public function destroy($noteId)
    {
        $note = LeadNote::findOrFail($noteId);

        try {
            $this->noteService->delete($note);
        } catch (\RuntimeException $e) {
            return $this->forbiddenResponse(__('messages.note_delete_unauthorized'));
        }

        return $this->successResponse([])->deleted('lead_note');
    }
}
