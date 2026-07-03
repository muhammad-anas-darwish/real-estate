<?php

namespace Modules\RealEstate\Policies;

use Modules\Auth\Entities\User;
use Modules\RealEstate\Entities\RentalCard;

class RentalCardPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('rental_cards.list');
    }

    public function view(User $user, RentalCard $card): bool
    {
        return $user->id === $card->owner_id
            || $user->hasPermissionTo('rental_cards.show');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('rental_cards.create');
    }

    public function update(User $user, RentalCard $card): bool
    {
        return $user->id === $card->owner_id
            && $user->hasPermissionTo('rental_cards.edit');
    }

    public function delete(User $user, RentalCard $card): bool
    {
        return $user->id === $card->owner_id
            && $user->hasPermissionTo('rental_cards.delete');
    }

    public function end(User $user, RentalCard $card): bool
    {
        return $user->id === $card->owner_id
            && $user->hasPermissionTo('rental_cards.end');
    }

    public function renew(User $user, RentalCard $card): bool
    {
        return $user->id === $card->owner_id
            && $user->hasPermissionTo('rental_cards.renew');
    }
}
