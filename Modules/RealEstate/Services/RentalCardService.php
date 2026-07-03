<?php

namespace Modules\RealEstate\Services;

use App\Services\BaseService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\RealEstate\DTOs\CreateRentalCardDTO;
use Modules\RealEstate\DTOs\EndRentalCardDTO;
use Modules\RealEstate\DTOs\RenewRentalCardDTO;
use Modules\RealEstate\DTOs\UpdateRentalCardDTO;
use Modules\RealEstate\Entities\Property;
use Modules\RealEstate\Entities\RentalCard;
use Modules\RealEstate\Enums\PropertyStatus;
use Modules\RealEstate\Enums\RentalCardStatus;

class RentalCardService extends BaseService
{
    protected const CACHE_TAG = 'rental_cards';

    protected const MAX_PRE_RENTAL_PHOTOS = 20;

    public function list(?int $propertyId = null, ?int $ownerId = null): LengthAwarePaginator
    {
        $query = RentalCard::query()
            ->filter()
            ->with(['property', 'owner', 'tenantUser', 'endedBy']);

        if ($propertyId) {
            $query->forProperty($propertyId);
        }

        if ($ownerId) {
            $query->forOwner($ownerId);
        }

        return $query
            ->orderBy(request('sort_by', 'created_at'), request('sort_order', 'desc'))
            ->paginate($this->getPerPage());
    }

    public function history(int $propertyId): LengthAwarePaginator
    {
        return $this->list($propertyId);
    }

    public function activeForProperty(int $propertyId): ?RentalCard
    {
        return RentalCard::forProperty($propertyId)
            ->active()
            ->with(['property', 'owner', 'tenantUser'])
            ->latest('start_date')
            ->first();
    }

    public function find(int $id): RentalCard
    {
        return RentalCard::with(['property', 'owner', 'tenantUser', 'endedBy'])
            ->findOrFail($id);
    }

    public function create(CreateRentalCardDTO $dto): RentalCard
    {
        return DB::transaction(function () use ($dto) {
            $property = Property::findOrFail($dto->property_id);

            $this->assertCanRent($property, $dto->owner_id);
            $this->assertDatesValid($dto->start_date, $dto->end_date);
            $this->assertTenantProvided($dto);

            $card = RentalCard::create(array_merge($dto->toArray(), [
                'status' => RentalCardStatus::ACTIVE,
            ]));

            $this->attachPhotos($card, $dto->pre_rental_photos);

            $property->update(['status' => PropertyStatus::RENTED]);
            $this->clearCache();

            return $card->fresh(['property', 'owner', 'tenantUser', 'media']);
        });
    }

    public function update(int $id, UpdateRentalCardDTO $dto): RentalCard
    {
        return DB::transaction(function () use ($id, $dto) {
            $card = $this->find($id);

            $this->assertOwner($card);
            $this->assertActive($card);

            $card->update($dto->toArray());
            $this->clearCache();

            return $card->fresh(['property', 'owner', 'tenantUser']);
        });
    }

    public function end(int $id, EndRentalCardDTO $dto): RentalCard
    {
        return DB::transaction(function () use ($id, $dto) {
            $card = $this->find($id);

            $this->assertOwner($card);
            $this->assertActive($card);

            $card->update([
                'status' => RentalCardStatus::ENDED,
                'ended_at' => $dto->ended_at ?? now(),
                'end_reason' => $dto->end_reason,
                'ended_by' => Auth::id(),
            ]);

            $this->restorePropertyToAvailable($card->property_id);
            $this->clearCache();

            return $card->fresh(['property', 'owner', 'tenantUser', 'endedBy']);
        });
    }

    public function renew(int $id, RenewRentalCardDTO $dto): RentalCard
    {
        return DB::transaction(function () use ($id, $dto) {
            $card = $this->find($id);

            $this->assertOwner($card);
            $this->assertActive($card);

            if (! $card->is_renewable) {
                throw new \RuntimeException(__('messages.rental_card_not_renewable'));
            }

            $this->assertDatesValid($card->start_date->format('Y-m-d'), $dto->end_date);

            $card->update([
                'end_date' => $dto->end_date,
                'terms' => $dto->terms ?? $card->terms,
                'notes' => $dto->notes ?? $card->notes,
                'renewed_at' => now(),
                'renewal_count' => $card->renewal_count + 1,
                'status' => RentalCardStatus::ACTIVE,
            ]);

            $this->clearCache();

            return $card->fresh(['property', 'owner', 'tenantUser']);
        });
    }

    public function delete(int $id): void
    {
        DB::transaction(function () use ($id) {
            $card = $this->find($id);

            $this->assertOwner($card);

            if ($card->status === RentalCardStatus::ACTIVE) {
                throw new \RuntimeException(__('messages.rental_card_cannot_delete_active'));
            }

            $card->delete();
            $this->clearCache();
        });
    }

    private function assertCanRent(Property $property, int $ownerId): void
    {
        if ((int) $property->publisher_id !== $ownerId) {
            throw new \RuntimeException(__('messages.rental_card_not_owner'));
        }

        if ($property->status === PropertyStatus::SOLD) {
            throw new \RuntimeException(__('messages.rental_card_property_sold'));
        }

        if ($property->status !== PropertyStatus::APPROVED) {
            throw new \RuntimeException(__('messages.rental_card_property_not_available'));
        }

        if (RentalCard::forProperty($property->id)->active()->exists()) {
            throw new \RuntimeException(__('messages.rental_card_active_exists'));
        }
    }

    /**
     * @param  \Illuminate\Http\UploadedFile[]  $photos
     */
    private function attachPhotos(RentalCard $card, array $photos): void
    {
        if (empty($photos)) {
            return;
        }

        foreach ($photos as $photo) {
            $card
                ->addMedia($photo)
                ->toMediaCollection('pre_rental');
        }
    }

    private function assertDatesValid(string $start, string $end): void
    {
        $startDate = \Carbon\Carbon::parse($start);
        $endDate = \Carbon\Carbon::parse($end);

        if ($endDate->lt($startDate)) {
            throw new \RuntimeException(__('messages.rental_card_end_before_start'));
        }
    }

    private function assertTenantProvided(CreateRentalCardDTO $dto): void
    {
        if ($dto->tenant_user_id) {
            return;
        }

        if (! $dto->external_tenant_name) {
            throw new \RuntimeException(__('messages.rental_card_external_tenant_name_required'));
        }
    }

    private function assertOwner(RentalCard $card): void
    {
        if ((int) $card->owner_id === (int) Auth::id()) {
            return;
        }

        if (Auth::user()?->hasRole('super-admin')) {
            return;
        }

        throw new \RuntimeException(__('messages.rental_card_not_owner'));
    }

    private function assertActive(RentalCard $card): void
    {
        if ($card->status !== RentalCardStatus::ACTIVE) {
            throw new \RuntimeException(__('messages.rental_card_not_active'));
        }
    }

    private function restorePropertyToAvailable(int $propertyId): void
    {
        $property = Property::find($propertyId);

        if (! $property) {
            return;
        }

        if ($property->status === PropertyStatus::SOLD) {
            return;
        }

        $property->update(['status' => PropertyStatus::APPROVED]);
    }
}
