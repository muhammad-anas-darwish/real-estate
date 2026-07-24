<?php

namespace Modules\Deposit\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Modules\Deposit\DTOs\CreateDepositDTO;
use Modules\Deposit\DTOs\UpdateDepositDTO;
use Modules\Deposit\Http\Requests\CancelDepositRequest;
use Modules\Deposit\Http\Requests\DepositFilterRequest;
use Modules\Deposit\Http\Requests\PayDepositRequest;
use Modules\Deposit\Http\Requests\RefundDepositRequest;
use Modules\Deposit\Http\Requests\ReleaseDepositRequest;
use Modules\Deposit\Http\Requests\StoreDepositRequest;
use Modules\Deposit\Http\Requests\UpdateDepositRequest;
use Modules\Deposit\Http\Resources\DepositResource;
use Modules\Deposit\Services\DepositService;

class DepositController extends Controller
{
    public function __construct(
        protected readonly DepositService $service
    ) {
        $this->applyPermissions(
            'deposits',
            ['index', 'show', 'store', 'update', 'destroy'],
            [
                'pay' => 'create',
                'release' => 'release',
                'refund' => 'refund',
                'cancel' => 'edit',
                'myDeposits' => 'list',
                'mySales' => 'list',
            ]
        );
    }

    /**
     * List all deposits with filtering.
     *
     * Super-admin sees all. Regular users see only their own deposits.
     *
     * @group Deposits
     *
     * @authenticated
     *
     * @queryParam property_id integer Filter by property.
     * @queryParam buyer_id integer Filter by buyer (super-admin only).
     * @queryParam seller_id integer Filter by seller (super-admin only).
     * @queryParam status string Filter by status (pending, held, released, refunded, disputed, cancelled).
     * @queryParam search string Search by reference number, terms, or notes.
     * @queryParam sort_by string Sort column (created_at, amount, held_at, released_at).
     * @queryParam sort_order string Sort direction (asc, desc).
     * @queryParam perPage integer Items per page (1-100).
     */
    public function index(DepositFilterRequest $request): JsonResponse
    {
        $user = Auth::user();

        $buyerId = null;
        $sellerId = null;

        if ($user->hasRole('super-admin')) {
            $buyerId = $request->integer('buyer_id') ?: null;
            $sellerId = $request->integer('seller_id') ?: null;
        }

        $deposits = $this->service->list($buyerId, $sellerId, $user->id);

        return $this->paginatedResponse(DepositResource::collection($deposits));
    }

    /**
     * Show a single deposit.
     *
     * @group Deposits
     *
     * @authenticated
     *
     * @urlParam id integer required The deposit ID.
     */
    public function show(int $id): JsonResponse
    {
        $deposit = $this->service->find($id);

        return $this->successResponse(DepositResource::make($deposit));
    }

    /**
     * Create a new deposit (pending status).
     *
     * The deposit is created in `pending` status. Use the `pay` endpoint
     * to fund and hold the deposit in escrow.
     *
     * @group Deposits
     *
     * @authenticated
     *
     * @bodyParam property_id integer required The property ID.
     * @bodyParam seller_id integer required The seller (property owner) user ID.
     * @bodyParam amount numeric required Deposit amount (min 0.01).
     * @bodyParam currency string Currency code (SAR or USD). Default: SAR.
     * @bodyParam terms string Optional terms text.
     * @bodyParam notes string Optional notes.
     */
    public function store(StoreDepositRequest $request): JsonResponse
    {
        $dto = CreateDepositDTO::fromRequest(array_merge(
            $request->validated(),
            ['buyer_id' => Auth::id()]
        ));

        $deposit = $this->service->create($dto);

        return $this->successResponse(
            DepositResource::make($deposit),
            __('deposits.created')
        )->created('deposit');
    }

    /**
     * Update a pending deposit's mutable fields.
     *
     * Only deposits in `pending` status can be updated.
     *
     * @group Deposits
     *
     * @authenticated
     *
     * @urlParam id integer required The deposit ID.
     *
     * @bodyParam amount numeric Optional. New amount.
     * @bodyParam terms string Optional. New terms.
     * @bodyParam notes string Optional. New notes.
     */
    public function update(int $id, UpdateDepositRequest $request): JsonResponse
    {
        $dto = UpdateDepositDTO::fromRequest($request->validated());
        $deposit = $this->service->update($id, $dto);

        return $this->successResponse(
            DepositResource::make($deposit),
            __('deposits.updated')
        );
    }

    /**
     * Pay and hold the deposit in escrow.
     *
     * Transfers the deposit amount from the buyer's wallet to the
     * platform escrow (liability) account. Status changes to `held`.
     *
     * @group Deposits
     *
     * @authenticated
     *
     * @urlParam id integer required The deposit ID.
     *
     * @bodyParam payment_method string required Payment method (balance or stripe).
     */
    public function pay(int $id, PayDepositRequest $request): JsonResponse
    {
        $deposit = $this->service->pay($id, $request->input('payment_method', 'balance'));

        return $this->successResponse(
            DepositResource::make($deposit),
            __('deposits.paid')
        );
    }

    /**
     * Release the held deposit to the seller.
     *
     * Transfers the deposit from escrow to the seller's wallet.
     * Only deposits in `held` status can be released.
     *
     * @group Deposits
     *
     * @authenticated
     *
     * @urlParam id integer required The deposit ID.
     *
     * @bodyParam notes string Optional. Release notes.
     */
    public function release(int $id, ReleaseDepositRequest $request): JsonResponse
    {
        $deposit = $this->service->release($id, $request->input('notes'));

        return $this->successResponse(
            DepositResource::make($deposit),
            __('deposits.released')
        );
    }

    /**
     * Refund the held deposit back to the buyer.
     *
     * Transfers the deposit from escrow back to the buyer's wallet.
     * Only deposits in `held` status can be refunded.
     *
     * @group Deposits
     *
     * @authenticated
     *
     * @urlParam id integer required The deposit ID.
     *
     * @bodyParam notes string Optional. Refund notes.
     */
    public function refund(int $id, RefundDepositRequest $request): JsonResponse
    {
        $deposit = $this->service->refund($id, $request->input('notes'));

        return $this->successResponse(
            DepositResource::make($deposit),
            __('deposits.refunded')
        );
    }

    /**
     * Cancel a deposit.
     *
     * Only deposits in `pending` or `disputed` status can be cancelled.
     *
     * @group Deposits
     *
     * @authenticated
     *
     * @urlParam id integer required The deposit ID.
     *
     * @bodyParam reason string required Reason for cancellation.
     */
    public function cancel(int $id, CancelDepositRequest $request): JsonResponse
    {
        $deposit = $this->service->cancel($id, $request->input('reason'));

        return $this->successResponse(
            DepositResource::make($deposit),
            __('deposits.cancelled')
        );
    }

    /**
     * Soft-delete a deposit.
     *
     * Only non-held/ non-disputed deposits can be deleted.
     *
     * @group Deposits
     *
     * @authenticated
     *
     * @urlParam id integer required The deposit ID.
     */
    public function destroy(int $id): JsonResponse
    {
        $this->service->delete($id);

        return $this->successResponse()->deleted('deposit');
    }

    /**
     * List deposits where the authenticated user is the buyer.
     *
     * @group Deposits
     *
     * @authenticated
     */
    public function myDeposits(DepositFilterRequest $request): JsonResponse
    {
        $deposits = $this->service->list(Auth::id(), null);

        return $this->paginatedResponse(DepositResource::collection($deposits));
    }

    /**
     * List deposits where the authenticated user is the seller.
     *
     * @group Deposits
     *
     * @authenticated
     */
    public function mySales(DepositFilterRequest $request): JsonResponse
    {
        $deposits = $this->service->list(null, Auth::id());

        return $this->paginatedResponse(DepositResource::collection($deposits));
    }
}
