<?php

namespace Modules\Deposit\Services;

use App\Services\BaseService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Deposit\DTOs\CreateDepositDTO;
use Modules\Deposit\DTOs\UpdateDepositDTO;
use Modules\Deposit\Entities\Deposit;
use Modules\Deposit\Enums\DepositStatus;
use Modules\Ledger\Enums\AccountType;
use Modules\Ledger\Services\AccountService;
use Modules\Ledger\Services\LedgerService;

class DepositService extends BaseService
{
    protected const CACHE_TAG = 'deposits';

    public function __construct(
        private readonly LedgerService $ledgerService,
        private readonly AccountService $accountService,
    ) {}

    public function list(?int $buyerId = null, ?int $sellerId = null, ?int $userId = null): LengthAwarePaginator
    {
        $query = Deposit::query()
            ->filter()
            ->with(['property', 'buyer', 'seller']);

        if ($userId && ! $buyerId && ! $sellerId) {
            $query->where(function ($q) use ($userId) {
                $q->where('buyer_id', $userId)
                    ->orWhere('seller_id', $userId);
            });
        } else {
            if ($buyerId) {
                $query->forBuyer($buyerId);
            }

            if ($sellerId) {
                $query->forSeller($sellerId);
            }
        }

        return $query
            ->orderBy(request('sort_by', 'created_at'), request('sort_order', 'desc'))
            ->paginate($this->getPerPage());
    }

    public function find(int $id): Deposit
    {
        return Deposit::with([
            'property', 'buyer', 'seller',
            'cancelledBy', 'releasedBy', 'refundedBy',
        ])->findOrFail($id);
    }

    public function create(CreateDepositDTO $dto): Deposit
    {
        $deposit = Deposit::create(array_merge($dto->toArray(), [
            'reference_number' => $this->generateReferenceNumber(),
            'status' => DepositStatus::PENDING,
        ]));

        $this->clearCache();

        return $deposit->fresh(['property', 'buyer', 'seller']);
    }

    public function update(int $id, UpdateDepositDTO $dto): Deposit
    {
        $deposit = $this->find($id);

        $this->assertPending($deposit);

        $deposit->update($dto->toArray());
        $this->clearCache();

        return $deposit->fresh(['property', 'buyer', 'seller']);
    }

    public function pay(int $id, string $paymentMethod = 'balance'): Deposit
    {
        return DB::transaction(function () use ($id, $paymentMethod) {
            $deposit = $this->find($id);

            $this->assertPending($deposit);

            $buyerAccount = $this->accountService->getUserAccount(
                $deposit->buyer, $deposit->currency
            );

            $escrowAccount = $this->accountService->getSystemAccount(
                AccountType::LIABILITY, $deposit->currency
            );

            if ($paymentMethod === 'balance') {
                $this->ledgerService->transfer(
                    from: $buyerAccount,
                    to: $escrowAccount,
                    amount: (float) $deposit->amount,
                    currency: $deposit->currency,
                    referenceType: 'deposit',
                    referenceId: $deposit->id,
                    description: "Earnest money deposit {$deposit->reference_number}",
                );
            }

            $deposit->update([
                'status' => DepositStatus::HELD,
                'held_at' => now(),
            ]);

            $this->clearCache();

            return $deposit->fresh([
                'property', 'buyer', 'seller',
                'cancelledBy', 'releasedBy', 'refundedBy',
            ]);
        });
    }

    public function release(int $id, ?string $notes = null): Deposit
    {
        return DB::transaction(function () use ($id, $notes) {
            $deposit = $this->find($id);

            $this->assertHeld($deposit);

            $escrowAccount = $this->accountService->getSystemAccount(
                AccountType::LIABILITY, $deposit->currency
            );

            $sellerAccount = $this->accountService->getUserAccount(
                $deposit->seller, $deposit->currency
            );

            $this->ledgerService->transfer(
                from: $escrowAccount,
                to: $sellerAccount,
                amount: (float) $deposit->amount,
                currency: $deposit->currency,
                referenceType: 'deposit',
                referenceId: $deposit->id,
                description: "Release earnest money {$deposit->reference_number} to seller",
            );

            $deposit->update([
                'status' => DepositStatus::RELEASED,
                'released_at' => now(),
                'released_by' => Auth::id(),
                'release_notes' => $notes,
            ]);

            $this->clearCache();

            return $deposit->fresh([
                'property', 'buyer', 'seller',
                'cancelledBy', 'releasedBy', 'refundedBy',
            ]);
        });
    }

    public function refund(int $id, ?string $notes = null): Deposit
    {
        return DB::transaction(function () use ($id, $notes) {
            $deposit = $this->find($id);

            $this->assertHeld($deposit);

            $escrowAccount = $this->accountService->getSystemAccount(
                AccountType::LIABILITY, $deposit->currency
            );

            $buyerAccount = $this->accountService->getUserAccount(
                $deposit->buyer, $deposit->currency
            );

            $this->ledgerService->transfer(
                from: $escrowAccount,
                to: $buyerAccount,
                amount: (float) $deposit->amount,
                currency: $deposit->currency,
                referenceType: 'deposit',
                referenceId: $deposit->id,
                description: "Refund earnest money {$deposit->reference_number} to buyer",
            );

            $deposit->update([
                'status' => DepositStatus::REFUNDED,
                'refunded_at' => now(),
                'refunded_by' => Auth::id(),
                'refund_notes' => $notes,
            ]);

            $this->clearCache();

            return $deposit->fresh([
                'property', 'buyer', 'seller',
                'cancelledBy', 'releasedBy', 'refundedBy',
            ]);
        });
    }

    public function cancel(int $id, string $reason): Deposit
    {
        return DB::transaction(function () use ($id, $reason) {
            $deposit = $this->find($id);

            $this->assertCancellable($deposit);

            $deposit->update([
                'status' => DepositStatus::CANCELLED,
                'cancelled_at' => now(),
                'cancelled_by' => Auth::id(),
                'cancellation_reason' => $reason,
            ]);

            $this->clearCache();

            return $deposit->fresh([
                'property', 'buyer', 'seller',
                'cancelledBy', 'releasedBy', 'refundedBy',
            ]);
        });
    }

    public function delete(int $id): void
    {
        DB::transaction(function () use ($id) {
            $deposit = $this->find($id);

            if (in_array($deposit->status, [DepositStatus::HELD, DepositStatus::DISPUTED])) {
                throw new \RuntimeException(__('deposits.cannot_delete_active'));
            }

            $deposit->delete();
            $this->clearCache();
        });
    }

    private function generateReferenceNumber(): string
    {
        $prefix = 'DEP-'.now()->format('Ymd').'-';
        $lastToday = Deposit::where('reference_number', 'like', $prefix.'%')
            ->orderBy('id', 'desc')
            ->first();

        if ($lastToday) {
            $lastSeq = (int) substr($lastToday->reference_number, -4);
            $seq = str_pad($lastSeq + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $seq = '0001';
        }

        return $prefix.$seq;
    }

    private function assertPending(Deposit $deposit): void
    {
        if ($deposit->status !== DepositStatus::PENDING) {
            throw new \RuntimeException(__('deposits.not_pending'));
        }
    }

    private function assertHeld(Deposit $deposit): void
    {
        if ($deposit->status !== DepositStatus::HELD) {
            throw new \RuntimeException(__('deposits.not_held'));
        }
    }

    private function assertCancellable(Deposit $deposit): void
    {
        $cancellable = [DepositStatus::PENDING, DepositStatus::DISPUTED];

        if (! in_array($deposit->status, $cancellable)) {
            throw new \RuntimeException(__('deposits.cannot_cancel'));
        }
    }
}
