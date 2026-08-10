<?php

namespace Modules\Crm\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Crm\DTOs\ChangeLeadStatusDTO;
use Modules\Crm\DTOs\LeadDTO;
use Modules\Crm\Http\Requests\ChangeLeadStatusRequest;
use Modules\Crm\Http\Requests\StoreLeadRequest;
use Modules\Crm\Http\Requests\UpdateLeadRequest;
use Modules\Crm\Http\Resources\LeadResource;
use Modules\Crm\Services\LeadService;

class LeadController extends Controller
{
    public function __construct(
        protected readonly LeadService $leadService
    ) {
        $this->applyPermissions(
            'leads',
            ['index', 'show', 'store', 'update'],
            [
                'archived' => 'list',
                'changeStatus' => 'change-status',
                'archive' => 'archive',
                'restore' => 'restore',
                'checkDuplicate' => 'list',
            ]
        );
    }

    public function index()
    {
        $leads = $this->leadService->list();

        return $this->paginatedResponse(LeadResource::collection($leads), __('messages.leads_listed'));
    }

    public function archived()
    {
        $leads = $this->leadService->archived();

        return $this->paginatedResponse(LeadResource::collection($leads), __('messages.leads_archived_listed'));
    }

    public function show($id)
    {
        $lead = $this->leadService->find($id);

        return $this->successResponse(LeadResource::make($lead), __('messages.leads_show'));
    }

    public function store(StoreLeadRequest $request)
    {
        $dto = LeadDTO::fromRequest($request->validated());
        $lead = $this->leadService->store($dto);

        return $this->successResponse(LeadResource::make($lead))->created('lead');
    }

    public function update(UpdateLeadRequest $request, $id)
    {
        $lead = $this->leadService->find($id);
        $dto = LeadDTO::fromRequest($request->validated());
        $lead = $this->leadService->update($lead, $dto);

        return $this->successResponse(LeadResource::make($lead), __('messages.leads_updated'))->updated('lead');
    }

    public function changeStatus(ChangeLeadStatusRequest $request, $id)
    {
        $lead = $this->leadService->find($id);
        $dto = ChangeLeadStatusDTO::fromRequest($request->validated());

        try {
            $lead = $this->leadService->changeStatus($lead, $dto);
        } catch (\InvalidArgumentException $e) {
            return $this->validationErrorResponse(['lost_reason' => [__('messages.lost_reason_required')]]);
        }

        return $this->successResponse(LeadResource::make($lead), __('messages.leads_status_changed'));
    }

    public function archive($id)
    {
        $lead = $this->leadService->find($id);
        $lead = $this->leadService->archive($lead);

        return $this->successResponse(LeadResource::make($lead), __('messages.leads_archived'));
    }

    public function restore($id)
    {
        try {
            $lead = $this->leadService->restore($id);
        } catch (\RuntimeException $e) {
            return $this->validationErrorResponse(['archived_at' => [__('messages.archive_period_expired')]]);
        }

        return $this->successResponse(LeadResource::make($lead), __('messages.leads_restored'));
    }

    public function checkDuplicate()
    {
        $phone = request('phone');
        $duplicate = $phone ? $this->leadService->checkDuplicatePhone($phone) : false;

        return $this->successResponse(['duplicate' => $duplicate]);
    }
}
