<?php

namespace Modules\Ledger\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Modules\Ledger\DTOs\PayrollDTO;
use Modules\Ledger\Entities\Payroll;
use Modules\Ledger\Http\Requests\SetupPayrollRequest;
use Modules\Ledger\Http\Requests\UpdatePayrollRequest;
use Modules\Ledger\Http\Resources\PayrollPaymentResource;
use Modules\Ledger\Http\Resources\PayrollResource;
use Modules\Ledger\Services\PayrollService;

class PayrollController extends Controller
{
    public function __construct(
        protected readonly PayrollService $payrollService
    ) {
        $this->applyPermissions('payroll', [], [
            'index' => 'list',
            'show' => 'list',
            'setup' => 'manage',
            'update' => 'manage',
            'run' => 'run',
            'payments' => 'list',
        ]);
    }

    public function index(): JsonResponse
    {
        $payrolls = $this->payrollService->listProvidersWithPayroll();

        return $this->paginatedResponse(
            PayrollResource::collection($payrolls),
            'Payroll providers listing'
        );
    }

    public function show(Payroll $payroll): JsonResponse
    {
        $payroll->load(['serviceProviderProfile.user', 'serviceProviderProfile.coverageAreas.city', 'payments']);

        return $this->successResponse(
            PayrollResource::make($payroll),
            'Payroll details'
        );
    }

    public function setup(SetupPayrollRequest $request): JsonResponse
    {
        $dto = PayrollDTO::fromRequest($request->validated());
        $payroll = $this->payrollService->setup($dto);

        return $this->successResponse(
            PayrollResource::make($payroll),
            'Payroll setup completed'
        )->created('payroll');
    }

    public function update(UpdatePayrollRequest $request, Payroll $payroll): JsonResponse
    {
        $dto = PayrollDTO::fromRequest($request->validated());
        $payroll = $this->payrollService->update($payroll, $dto);

        return $this->successResponse(
            PayrollResource::make($payroll),
            'Payroll updated'
        )->updated('payroll');
    }

    public function run(): JsonResponse
    {
        $results = $this->payrollService->runPayroll(Auth::user());

        $paid = count(array_filter($results, fn ($r) => $r['status'] === 'paid'));
        $failed = count(array_filter($results, fn ($r) => $r['status'] === 'failed'));
        $skipped = count(array_filter($results, fn ($r) => $r['status'] === 'skipped'));

        return $this->successResponse([
            'summary' => [
                'total' => count($results),
                'paid' => $paid,
                'failed' => $failed,
                'skipped' => $skipped,
            ],
            'details' => $results,
        ], 'Payroll run completed');
    }

    public function payments(): JsonResponse
    {
        $payrollId = request()->query('payroll_id');
        $payments = $this->payrollService->getPayments($payrollId ? (int) $payrollId : null);

        return $this->paginatedResponse(
            PayrollPaymentResource::collection($payments),
            'Payroll payments history'
        );
    }
}
