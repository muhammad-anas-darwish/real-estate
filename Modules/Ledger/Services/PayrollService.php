<?php

namespace Modules\Ledger\Services;

use App\Services\BaseService;
use Illuminate\Support\Facades\DB;
use Modules\Auth\Entities\User;
use Modules\Ledger\DTOs\JournalEntryDTO;
use Modules\Ledger\DTOs\PayrollDTO;
use Modules\Ledger\Entities\Account;
use Modules\Ledger\Entities\Payroll;
use Modules\Ledger\Entities\PayrollPayment;
use Modules\Ledger\Enums\PayrollPaymentStatus;
use Modules\Ledger\Enums\PayrollType;
use Modules\ServiceProvider\Entities\ServiceProviderProfile;
use Modules\ServiceProvider\Entities\ServiceRequest;

class PayrollService extends BaseService
{
    public const CACHE_TAG = 'payroll';

    public function __construct(
        private readonly AccountService $accountService,
        private readonly JournalEntryService $journalService
    ) {}

    public function listProvidersWithPayroll(): \Illuminate\Pagination\LengthAwarePaginator
    {
        return Payroll::query()
            ->with(['serviceProviderProfile.user', 'serviceProviderProfile.coverageAreas.city'])
            ->orderBy('is_active', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($this->getPerPage());
    }

    public function setup(PayrollDTO $dto): Payroll
    {
        $this->validatePayrollSetup($dto);

        $profileExists = ServiceProviderProfile::findOrFail($dto->serviceProviderProfileId);

        return DB::transaction(function () use ($dto) {
            $payroll = Payroll::updateOrCreate(
                ['service_provider_profile_id' => $dto->serviceProviderProfileId],
                $dto->toArray()
            );
            $this->clearCache();

            return $payroll->load('serviceProviderProfile.user');
        });
    }

    public function update(Payroll $payroll, PayrollDTO $dto): Payroll
    {
        if ($dto->type !== null && $dto->type !== $payroll->type->value) {
            $payroll->update(['type' => $dto->type]);
        }

        $payroll->update($dto->toArray());
        $this->clearCache();

        return $payroll->load('serviceProviderProfile.user');
    }

    public function runPayroll(User $runByUser): array
    {
        $activePayrolls = Payroll::where('is_active', true)
            ->with('serviceProviderProfile.user')
            ->get();

        $results = [];
        $now = now();

        foreach ($activePayrolls as $payroll) {
            $profile = $payroll->serviceProviderProfile;

            if (! $profile || ! $profile->is_verified) {
                $results[] = [
                    'payroll_id' => $payroll->id,
                    'provider_name' => $profile?->user?->name ?? 'Unknown',
                    'status' => 'skipped',
                    'reason' => 'Provider not verified or not found',
                ];

                continue;
            }

            $periodStart = $this->getLastPaymentDate($payroll) ?? $payroll->start_date?->format('Y-m-d') ?? $now->copy()->startOfMonth()->format('Y-m-d');
            $periodEnd = $now->copy()->subDay()->format('Y-m-d');

            if ($periodStart >= $now->format('Y-m-d')) {
                $results[] = [
                    'payroll_id' => $payroll->id,
                    'provider_name' => $profile->user->name,
                    'status' => 'skipped',
                    'reason' => 'Period has not started yet',
                ];

                continue;
            }

            $salaryAmount = 0;
            $tasksCount = 0;
            $tasksAmount = 0;

            if (in_array($payroll->type, [PayrollType::MONTHLY, PayrollType::BOTH])) {
                $monthsDiff = max(1, $now->copy()->startOfMonth()->diffInMonths($periodStart) + 1);
                $salaryAmount = ((float) ($payroll->base_salary ?? 0)) * max(1, $monthsDiff);
            }

            if (in_array($payroll->type, [PayrollType::PER_TASK, PayrollType::BOTH])) {
                $completedTasks = ServiceRequest::where('provider_id', $profile->id)
                    ->where('status', \Modules\ServiceProvider\Enums\ServiceRequestStatus::COMPLETED->value)
                    ->whereNotNull('completed_at')
                    ->whereBetween('completed_at', [$periodStart.' 00:00:00', $periodEnd.' 23:59:59'])
                    ->where('is_paid', true)
                    ->count();

                $tasksCount = $completedTasks;
                $tasksAmount = $completedTasks * ((float) ($payroll->per_task_rate ?? 0));
            }

            $totalAmount = $salaryAmount + $tasksAmount;

            if ($totalAmount <= 0) {
                $results[] = [
                    'payroll_id' => $payroll->id,
                    'provider_name' => $profile->user->name,
                    'status' => 'skipped',
                    'reason' => 'No amount to pay (zero earnings in period)',
                ];

                continue;
            }

            try {
                $providerUser = $profile->user;
                $providerAccount = $this->accountService->getUserAccount($providerUser);
                $expenseAccount = Account::where('account_number', '5001')->first();

                if (! $expenseAccount) {
                    $expenseAccount = Account::where('code', 'ACC-5001')->first();
                }

                if (! $expenseAccount) {
                    throw new \RuntimeException('Salary expense account (5001) not found in chart of accounts.');
                }

                $payment = PayrollPayment::create([
                    'payroll_id' => $payroll->id,
                    'service_provider_profile_id' => $profile->id,
                    'amount' => $totalAmount,
                    'salary_amount' => $salaryAmount,
                    'tasks_count' => $tasksCount,
                    'tasks_amount' => $tasksAmount,
                    'period_start' => $periodStart,
                    'period_end' => $periodEnd,
                    'status' => PayrollPaymentStatus::PENDING,
                ]);

                $journalLines = [
                    [
                        'account_id' => $expenseAccount->id,
                        'debit_amount' => $totalAmount,
                        'credit_amount' => 0,
                        'description' => 'Payroll: '.$profile->user->name.' ('.$periodStart.' to '.$periodEnd.')',
                    ],
                    [
                        'account_id' => $providerAccount->id,
                        'debit_amount' => 0,
                        'credit_amount' => $totalAmount,
                        'description' => 'Payroll payment for '.$profile->user->name,
                    ],
                ];

                $journalDto = new JournalEntryDTO(
                    entryDate: now()->format('Y-m-d'),
                    lines: $journalLines,
                    description: 'Payroll payment: '.$profile->user->name.' - '.\Illuminate\Support\Str::padLeft($periodStart, 10).' to '.\Illuminate\Support\Str::padLeft($periodEnd, 10),
                    reference: 'PAYROLL-'.$payment->id,
                );

                $journalEntry = $this->journalService->create($journalDto, $runByUser);
                $journalEntry = $this->journalService->post($journalEntry, $runByUser);

                $payment->update([
                    'status' => PayrollPaymentStatus::PAID,
                    'paid_at' => now(),
                    'journal_entry_id' => $journalEntry->id,
                ]);

                $results[] = [
                    'payroll_id' => $payroll->id,
                    'provider_name' => $profile->user->name,
                    'status' => 'paid',
                    'amount' => $totalAmount,
                    'salary_amount' => $salaryAmount,
                    'tasks_count' => $tasksCount,
                    'tasks_amount' => $tasksAmount,
                    'payment_id' => $payment->id,
                    'period' => $periodStart.' to '.$periodEnd,
                ];
            } catch (\Throwable $e) {
                $results[] = [
                    'payroll_id' => $payroll->id,
                    'provider_name' => $profile->user->name,
                    'status' => 'failed',
                    'reason' => $e->getMessage(),
                ];

                if (isset($payment)) {
                    $payment->update(['status' => PayrollPaymentStatus::FAILED, 'notes' => $e->getMessage()]);
                }
            }
        }

        $this->clearCache();

        return $results;
    }

    public function getPayments(?int $payrollId = null): \Illuminate\Pagination\LengthAwarePaginator
    {
        $query = PayrollPayment::query()
            ->with(['payroll.serviceProviderProfile.user', 'journalEntry']);

        if ($payrollId) {
            $query->where('payroll_id', $payrollId);
        }

        return $query->orderByDesc('period_start')
            ->orderByDesc('id')
            ->filter()
            ->paginate($this->getPerPage());
    }

    private function getLastPaymentDate(Payroll $payroll): ?string
    {
        $lastPayment = $payroll->payments()
            ->where('status', PayrollPaymentStatus::PAID)
            ->orderByDesc('period_end')
            ->first();

        return $lastPayment?->period_end?->addDay()->format('Y-m-d');
    }

    private function validatePayrollSetup(PayrollDTO $dto): void
    {
        $type = PayrollType::from($dto->type);

        if (in_array($type, [PayrollType::MONTHLY, PayrollType::BOTH]) && ! $dto->baseSalary) {
            throw new \RuntimeException('Base salary is required for monthly and combined payroll types.');
        }

        if (in_array($type, [PayrollType::PER_TASK, PayrollType::BOTH]) && ! $dto->perTaskRate) {
            throw new \RuntimeException('Per-task rate is required for per-task and combined payroll types.');
        }
    }
}
