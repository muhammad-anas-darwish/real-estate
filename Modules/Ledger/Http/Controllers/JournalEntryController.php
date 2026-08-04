<?php

namespace Modules\Ledger\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Modules\Ledger\DTOs\JournalEntryDTO;
use Modules\Ledger\Entities\JournalEntry;
use Modules\Ledger\Http\Requests\StoreJournalEntryRequest;
use Modules\Ledger\Http\Requests\UpdateJournalEntryRequest;
use Modules\Ledger\Http\Resources\JournalEntryResource;
use Modules\Ledger\Services\JournalEntryService;

class JournalEntryController extends Controller
{
    public function __construct(
        protected readonly JournalEntryService $journalService
    ) {
        $this->applyPermissions('journal_entries', ['index', 'show', 'store', 'update', 'destroy'], [
            'post' => 'post',
        ]);

        $this->middleware('permission:trial_balance.view', ['only' => ['trialBalance']]);
    }

    public function index(): JsonResponse
    {
        $entries = $this->journalService->list();

        return $this->paginatedResponse(
            JournalEntryResource::collection($entries),
            'Journal entries list'
        );
    }

    public function show(JournalEntry $journalEntry): JsonResponse
    {
        $entry = $this->journalService->find($journalEntry->id);

        return $this->successResponse(
            JournalEntryResource::make($entry),
            'Journal entry details'
        );
    }

    public function store(StoreJournalEntryRequest $request): JsonResponse
    {
        $dto = JournalEntryDTO::fromRequest($request->validated());
        $entry = $this->journalService->create($dto, Auth::user());

        return $this->successResponse(
            JournalEntryResource::make($entry),
            'Journal entry created'
        )->created('journal entry');
    }

    public function update(UpdateJournalEntryRequest $request, JournalEntry $journalEntry): JsonResponse
    {
        $dto = JournalEntryDTO::fromRequest($request->validated());
        $entry = $this->journalService->update($journalEntry, $dto);

        return $this->successResponse(
            JournalEntryResource::make($entry),
            'Journal entry updated'
        )->updated('journal entry');
    }

    public function destroy(JournalEntry $journalEntry): JsonResponse
    {
        $this->journalService->delete($journalEntry);

        return $this->successResponse([], 'Journal entry deleted')
            ->deleted('journal entry');
    }

    public function post(JournalEntry $journalEntry): JsonResponse
    {
        $entry = $this->journalService->post($journalEntry, Auth::user());

        return $this->successResponse(
            JournalEntryResource::make($entry),
            'Journal entry posted successfully'
        );
    }

    public function trialBalance(): JsonResponse
    {
        $request = request()->validate([
            'date_from' => ['required', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $data = $this->journalService->getTrialBalance(
            $request['date_from'],
            $request['date_to'] ?? null
        );

        return $this->successResponse($data, 'Trial balance report');
    }
}
