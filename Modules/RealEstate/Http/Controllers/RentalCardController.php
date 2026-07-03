<?php

namespace Modules\RealEstate\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Modules\RealEstate\DTOs\CreateRentalCardDTO;
use Modules\RealEstate\DTOs\EndRentalCardDTO;
use Modules\RealEstate\DTOs\RenewRentalCardDTO;
use Modules\RealEstate\DTOs\UpdateRentalCardDTO;
use Modules\RealEstate\Http\Requests\CreateRentalCardRequest;
use Modules\RealEstate\Http\Requests\EndRentalCardRequest;
use Modules\RealEstate\Http\Requests\RenewRentalCardRequest;
use Modules\RealEstate\Http\Requests\RentalCardFilterRequest;
use Modules\RealEstate\Http\Requests\UpdateRentalCardRequest;
use Modules\RealEstate\Http\Resources\RentalCardResource;
use Modules\RealEstate\Services\RentalCardService;

class RentalCardController extends Controller
{
    public function __construct(
        protected readonly RentalCardService $service
    ) {
        $this->applyPermissions(
            'rental_cards',
            ['index', 'show', 'store', 'update', 'destroy'],
            [
                'active' => 'show',
                'history' => 'list',
                'end' => 'end',
                'renew' => 'renew',
            ]
        );
    }

    /**
     * List rental cards.
     *
     * Non-super-admin users only see cards they own.
     *
     * @group Rental Cards
     *
     * @authenticated
     *
     * @queryParam property_id integer Optional. Filter by property ID. Example: 5
     * @queryParam owner_id integer Optional. Filter by owner ID. Super-admin only; ignored for regular users. Example: 3
     * @queryParam tenant_user_id integer Optional. Filter by tenant user ID. Example: 42
     * @queryParam status string Optional. Filter by status (active, ended, cancelled, renewed). Example: active
     * @queryParam is_renewable boolean Optional. Filter by renewable flag. Example: true
     * @queryParam search string Optional. Search tenant name, phone, email, or notes. Example: John
     * @queryParam start_date date Optional. Filter cards with start_date >= value. Example: 2026-01-01
     * @queryParam end_date date Optional. Filter cards with end_date <= value. Example: 2026-12-31
     * @queryParam sort_by string Optional. Sort column (created_at, start_date, end_date). Example: created_at
     * @queryParam sort_order string Optional. Sort direction (asc, desc). Example: desc
     * @queryParam perPage integer Optional. Items per page (1-100). Example: 15
     * @queryParam page integer Optional. Page number. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "message": null,
     *   "data": [{"id": 1, "property_id": 5, "status": "active"}],
     *   "pagination": {"total": 1, "per_page": 15, "current_page": 1, "last_page": 1, "from": 1, "to": 1}
     * }
     */
    public function index(RentalCardFilterRequest $request): JsonResponse
    {
        $user = Auth::user();
        $ownerId = $request->integer('owner_id');

        if (! $user->hasRole('super-admin')) {
            $ownerId = $ownerId ?: $user->id;
        }

        $cards = $this->service->list(
            propertyId: $request->integer('property_id') ?: null,
            ownerId: $ownerId ?: null,
        );

        return $this->paginatedResponse(RentalCardResource::collection($cards));
    }

    /**
     * Show a single rental card.
     *
     * @group Rental Cards
     *
     * @authenticated
     *
     * @urlParam id integer required The rental card ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "message": null,
     *   "data": {"id": 1, "property_id": 5, "status": "active", "tenant": {"type": "registered"}}
     * }
     * @response 403 {"success": false, "message": "Forbidden"}
     * @response 404 {"success": false, "message": "Not found"}
     */
    public function show(int $id): JsonResponse
    {
        $card = $this->service->find($id);

        if (Gate::denies('view', $card)) {
            return $this->forbiddenResponse();
        }

        return $this->successResponse(RentalCardResource::make($card));
    }

    /**
     * Get the active rental card for a property (if any).
     *
     * @group Rental Cards
     *
     * @authenticated
     *
     * @urlParam propertyId integer required The property ID. Example: 5
     *
     * @response 200 {
     *   "success": true,
     *   "message": "No active rental card found for this property",
     *   "data": []
     * }
     */
    public function active(int $propertyId): JsonResponse
    {
        $card = $this->service->activeForProperty($propertyId);

        if (! $card) {
            return $this->successResponse([], __('messages.no_active_rental'));
        }

        if (Gate::denies('view', $card)) {
            return $this->forbiddenResponse();
        }

        return $this->successResponse(RentalCardResource::make($card));
    }

    /**
     * List all rental cards (history) for a specific property.
     *
     * @group Rental Cards
     *
     * @authenticated
     *
     * @urlParam propertyId integer required The property ID. Example: 5
     *
     * @queryParam page integer Optional. Page number. Example: 1
     * @queryParam perPage integer Optional. Items per page. Example: 15
     *
     * @response 200 {
     *   "success": true,
     *   "data": [],
     *   "pagination": {"total": 0, "per_page": 15, "current_page": 1, "last_page": 1}
     * }
     */
    public function history(int $propertyId, RentalCardFilterRequest $request): JsonResponse
    {
        $cards = $this->service->history($propertyId);

        return $this->paginatedResponse(RentalCardResource::collection($cards));
    }

    /**
     * Create a new rental card.
     *
     * Creates an active rental card for the given property. The property's
     * status is automatically transitioned to `rented`. Either a registered
     * tenant (user_id) or an external tenant (name required) must be provided.
     * Up to 20 pre-rental photos can be attached (jpeg/png/webp, 5 MB each).
     *
     * @group Rental Cards
     *
     * @authenticated
     *
     * @bodyParam property_id integer required The property ID. Must be owned by the caller and currently `approved`. Example: 5
     * @bodyParam tenant_user_id integer Optional. ID of a registered tenant user. Example: 42
     * @bodyParam external_tenant_name string Optional. Required when `tenant_user_id` is omitted. Example: John External
     * @bodyParam external_tenant_phone string Optional. Max 32 chars. Example: +966500000000
     * @bodyParam external_tenant_email string Optional. Must be a valid email. Example: john@example.com
     * @bodyParam external_tenant_id_notes string Optional. Free-text identity notes. Example: National ID 1234567890
     * @bodyParam start_date date required Rental start date (today or later). Example: 2026-07-01
     * @bodyParam end_date date required Rental end date (must be after start_date). Example: 2027-01-01
     * @bodyParam terms string Optional. Free-text terms (max 5000 chars). Example: Monthly rent 3000 SAR
     * @bodyParam notes string Optional. Free-text notes (max 5000 chars). Example: Tenant promised to repaint living room
     * @bodyParam is_renewable boolean Optional. Whether the card can be renewed. Default: false. Example: true
     * @bodyParam pre_rental_photos file[] Optional. Up to 20 image files (jpeg/png/webp, 5 MB each).
     *
     * @response 201 {
     *   "success": true,
     *   "message": "Rental card has been created successfully",
     *   "data": {"id": 1, "property_id": 5, "status": "active", "is_renewable": true}
     * }
     * @response 422 {"success": false, "message": "The given data was invalid.", "errors": {"property_id": ["..."]}}
     * @response 500 {"success": false, "message": "This property is not available for renting."}
     */
    public function store(CreateRentalCardRequest $request): JsonResponse
    {
        $dto = CreateRentalCardDTO::fromRequest(array_merge(
            $request->validated(),
            ['owner_id' => Auth::id()]
        ));

        $card = $this->service->create($dto);

        return $this->successResponse(
            RentalCardResource::make($card),
            __('messages.rental_card_created')
        )->created('rental_card');
    }

    /**
     * Update a rental card's mutable fields.
     *
     * Only `end_date`, `terms`, `notes`, and `is_renewable` are mutable.
     * `property_id`, `owner_id`, `tenant_user_id`, and `start_date` are
     * immutable after creation; submit a new card instead.
     *
     * @group Rental Cards
     *
     * @authenticated
     *
     * @urlParam id integer required The rental card ID. Example: 1
     *
     * @bodyParam end_date date Optional. New end date. Example: 2027-06-01
     * @bodyParam terms string Optional. Max 5000 chars. Example: Updated terms
     * @bodyParam notes string Optional. Max 5000 chars. Example: Updated notes
     * @bodyParam is_renewable boolean Optional. Example: true
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Rental card has been updated successfully",
     *   "data": {"id": 1, "terms": "Updated terms"}
     * }
     * @response 403 {"success": false, "message": "Forbidden"}
     */
    public function update(int $id, UpdateRentalCardRequest $request): JsonResponse
    {
        $card = $this->service->find($id);

        if (Gate::denies('update', $card)) {
            return $this->forbiddenResponse();
        }

        $dto = UpdateRentalCardDTO::fromRequest($request->validated());
        $card = $this->service->update($id, $dto);

        return $this->successResponse(
            RentalCardResource::make($card),
            __('messages.rental_card_updated')
        );
    }

    /**
     * End a rental card early.
     *
     * Sets the card status to `ended`, records `ended_at` and `ended_by`,
     * and transitions the property back to `approved` (unless the property
     * is `sold`).
     *
     * @group Rental Cards
     *
     * @authenticated
     *
     * @urlParam id integer required The rental card ID. Example: 1
     *
     * @bodyParam ended_at date Optional. End date (today or earlier). Default: now. Example: 2026-08-15
     * @bodyParam end_reason string Optional. Free-text reason (max 1000 chars). Example: Tenant moved abroad
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Rental card has been ended",
     *   "data": {"id": 1, "status": "ended", "end_reason": "Tenant moved abroad"}
     * }
     * @response 403 {"success": false, "message": "Forbidden"}
     * @response 500 {"success": false, "message": "This rental card is not active."}
     */
    public function end(int $id, EndRentalCardRequest $request): JsonResponse
    {
        $card = $this->service->find($id);

        if (Gate::denies('end', $card)) {
            return $this->forbiddenResponse();
        }

        $dto = EndRentalCardDTO::fromRequest($request->validated());
        $card = $this->service->end($id, $dto);

        return $this->successResponse(
            RentalCardResource::make($card),
            __('messages.rental_card_ended')
        );
    }

    /**
     * Renew a rental card.
     *
     * Extends the end date and increments the renewal counter. Only
     * works for cards with `is_renewable = true`. The end date must
     * be on or after the current start date.
     *
     * @group Rental Cards
     *
     * @authenticated
     *
     * @urlParam id integer required The rental card ID. Example: 1
     *
     * @bodyParam end_date date required New end date. Example: 2027-12-01
     * @bodyParam terms string Optional. Max 5000 chars. Example: Renewed terms
     * @bodyParam notes string Optional. Max 5000 chars. Example: Renewed for another year
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Rental card has been renewed",
     *   "data": {"id": 1, "end_date": "2027-12-01", "renewal_count": 1}
     * }
     * @response 403 {"success": false, "message": "Forbidden"}
     * @response 500 {"success": false, "message": "This rental card is not marked as renewable."}
     */
    public function renew(int $id, RenewRentalCardRequest $request): JsonResponse
    {
        $card = $this->service->find($id);

        if (Gate::denies('renew', $card)) {
            return $this->forbiddenResponse();
        }

        $dto = RenewRentalCardDTO::fromRequest($request->validated());
        $card = $this->service->renew($id, $dto);

        return $this->successResponse(
            RentalCardResource::make($card),
            __('messages.rental_card_renewed')
        );
    }

    /**
     * Soft-delete a rental card.
     *
     * Only non-active (ended/cancelled/renewed) cards can be deleted.
     *
     * @group Rental Cards
     *
     * @authenticated
     *
     * @urlParam id integer required The rental card ID. Example: 1
     *
     * @response 200 {"success": true, "message": "rental_card deleted successfully"}
     * @response 403 {"success": false, "message": "Forbidden"}
     * @response 500 {"success": false, "message": "An active rental card cannot be deleted. End it first."}
     */
    public function destroy(int $id): JsonResponse
    {
        $card = $this->service->find($id);

        if (Gate::denies('delete', $card)) {
            return $this->forbiddenResponse();
        }

        $this->service->delete($id);

        return $this->successResponse()->deleted('rental_card');
    }
}
