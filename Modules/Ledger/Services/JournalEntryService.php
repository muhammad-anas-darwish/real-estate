<?php

namespace Modules\Ledger\Services;

use App\Services\BaseService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Auth\Entities\User;
use Modules\Ledger\DTOs\JournalEntryDTO;
use Modules\Ledger\Entities\Account;
use Modules\Ledger\Entities\JournalEntry;
use Modules\Ledger\Entities\JournalEntryLine;
use Modules\Ledger\Enums\EntryType;
use Modules\Ledger\Enums\JournalEntryStatus;

class JournalEntryService extends BaseService
{
    public const CACHE_TAG = 'journal_entries';

    public function __construct(
        private readonly LedgerService $ledgerService
    ) {}

    public function list(): \Illuminate\Pagination\LengthAwarePaginator
    {
        return JournalEntry::query()
            ->with('createdBy')
            ->orderByDesc('entry_date')
            ->orderByDesc('id')
            ->filter()
            ->paginate($this->getPerPage());
    }

    public function find(int $id): JournalEntry
    {
        return JournalEntry::with('lines.account', 'createdBy', 'postedBy')->findOrFail($id);
    }

    public function create(JournalEntryDTO $dto, User $user): JournalEntry
    {
        $this->validateLineBalance($dto->lines);

        $entry = JournalEntry::create([
            'journal_number' => $this->generateJournalNumber(),
            'entry_date' => $dto->entryDate,
            'description' => $dto->description,
            'reference' => $dto->reference,
            'notes' => $dto->notes,
            'status' => JournalEntryStatus::DRAFT,
            'created_by_id' => $user->id,
        ]);

        $this->syncLines($entry, $dto->lines);

        return $entry->load('lines.account');
    }

    public function update(JournalEntry $entry, JournalEntryDTO $dto): JournalEntry
    {
        if ($entry->isPosted()) {
            throw new \RuntimeException('Cannot update a posted journal entry.');
        }

        $entry->update($dto->toArray());

        $lines = $dto->lines;
        if (! empty($lines)) {
            $this->validateLineBalance($lines);
            $this->syncLines($entry, $lines);
        }

        return $entry->load('lines.account');
    }

    public function delete(JournalEntry $entry): void
    {
        if ($entry->isPosted()) {
            throw new \RuntimeException('Cannot delete a posted journal entry.');
        }

        $entry->lines()->delete();
        $entry->delete();
    }

    public function post(JournalEntry $entry, User $user): JournalEntry
    {
        if ($entry->isPosted()) {
            throw new \RuntimeException('Journal entry is already posted.');
        }

        $entry->load('lines.account');

        if (! $entry->isBalanced()) {
            throw new \RuntimeException(
                'Journal entry is not balanced. Debit: '.$entry->total_debit.', Credit: '.$entry->total_credit
            );
        }

        $batchId = 'JE-'.$entry->id.'-'.Str::uuid()->toString();

        foreach ($entry->lines as $line) {
            $account = Account::where('id', $line->account_id)->lockForUpdate()->firstOrFail();
            $amount = max((float) $line->debit_amount, (float) $line->credit_amount);
            $isDebit = (float) $line->debit_amount > 0;

            $balanceAfter = $isDebit
                ? (float) $account->current_balance - $amount
                : (float) $account->current_balance + $amount;

            \Modules\Ledger\Entities\AccountEntry::create([
                'account_id' => $account->id,
                'batch_id' => $batchId,
                'entry_type' => $isDebit ? EntryType::DEBIT : EntryType::CREDIT,
                'amount' => $amount,
                'currency' => $account->currency,
                'balance_after' => $balanceAfter,
                'reference_type' => 'journal_entry',
                'reference_id' => $entry->id,
                'description' => $line->description ?? $entry->description,
                'idempotency_key' => $batchId.'-L'.$line->id,
                'posted_at' => now(),
            ]);

            $account->update(['current_balance' => $balanceAfter]);
            $line->update(['ledger_batch_id' => $batchId]);
        }

        $entry->update([
            'status' => JournalEntryStatus::POSTED,
            'posted_by_id' => $user->id,
            'posted_at' => now(),
        ]);

        $this->clearCache();

        return $entry->fresh()->load('lines.account', 'postedBy');
    }

    public function getTrialBalance(string $dateFrom, ?string $dateTo = null): array
    {
        $dateTo = $dateTo ?? now()->format('Y-m-d');

        $accounts = Account::where('is_active', true)
            ->whereNotNull('account_category')
            ->with(['entries' => function ($q) use ($dateFrom, $dateTo) {
                $q->whereBetween('posted_at', [$dateFrom.' 00:00:00', $dateTo.' 23:59:59'])
                    ->orderBy('posted_at');
            }])
            ->orderBy('account_number')
            ->get();

        $categories = [];
        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($accounts as $account) {
            $debitSum = (float) $account->entries->where('entry_type', \Modules\Ledger\Enums\EntryType::DEBIT)->sum('amount');
            $creditSum = (float) $account->entries->where('entry_type', \Modules\Ledger\Enums\EntryType::CREDIT)->sum('amount');
            $balance = (float) $account->current_balance;

            $categoryValue = $account->account_category?->value ?? 'other';

            if (! isset($categories[$categoryValue])) {
                $categories[$categoryValue] = [
                    'category' => $categoryValue,
                    'category_label' => $account->account_category?->label() ?? $categoryValue,
                    'category_label_ar' => $account->account_category?->labelAr() ?? $categoryValue,
                    'accounts' => [],
                    'total_debit' => 0,
                    'total_credit' => 0,
                    'net_balance' => 0,
                ];
            }

            $categories[$categoryValue]['accounts'][] = [
                'id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'account_number' => $account->account_number,
                'debit_sum' => $debitSum,
                'credit_sum' => $creditSum,
                'current_balance' => $balance,
                'normal_balance' => $account->account_category?->normalBalance(),
            ];

            $categories[$categoryValue]['total_debit'] += $debitSum;
            $categories[$categoryValue]['total_credit'] += $creditSum;
            $categories[$categoryValue]['net_balance'] = $categories[$categoryValue]['total_debit'] - $categories[$categoryValue]['total_credit'];

            $totalDebit += $debitSum;
            $totalCredit += $creditSum;
        }

        return [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'categories' => array_values($categories),
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'is_balanced' => abs($totalDebit - $totalCredit) < 0.01,
            'difference' => round($totalDebit - $totalCredit, 2),
        ];
    }

    private function validateLineBalance(array $lines): void
    {
        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($lines as $line) {
            $debit = (float) ($line['debit_amount'] ?? 0);
            $credit = (float) ($line['credit_amount'] ?? 0);

            if ($debit < 0 || $credit < 0) {
                throw new \RuntimeException('Amounts must be positive.');
            }

            if ($debit == 0 && $credit == 0) {
                throw new \RuntimeException('Each line must have either a debit or credit amount.');
            }

            if ($debit > 0 && $credit > 0) {
                throw new \RuntimeException('A line cannot have both debit and credit amounts.');
            }

            $totalDebit += $debit;
            $totalCredit += $credit;
        }

        if (abs($totalDebit - $totalCredit) > 0.001) {
            throw new \RuntimeException(
                "Journal entry is not balanced. Total Debit: {$totalDebit}, Total Credit: {$totalCredit}"
            );
        }
    }

    private function syncLines(JournalEntry $entry, array $lines): void
    {
        $entry->lines()->delete();

        foreach ($lines as $index => $lineData) {
            JournalEntryLine::forceCreate([
                'journal_entry_id' => $entry->id,
                'account_id' => $lineData['account_id'],
                'description' => $lineData['description'] ?? null,
                'debit_amount' => (float) ($lineData['debit_amount'] ?? 0),
                'credit_amount' => (float) ($lineData['credit_amount'] ?? 0),
                'sort_order' => $index,
            ]);
        }
    }

    private function generateJournalNumber(): string
    {
        $year = date('Y');
        $month = date('m');
        $count = JournalEntry::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->lockForUpdate()
            ->count();

        return sprintf('JE-%s%s-%04d', $year, $month, $count + 1);
    }
}
