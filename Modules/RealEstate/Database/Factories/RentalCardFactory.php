<?php

namespace Modules\RealEstate\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Auth\Entities\User;
use Modules\RealEstate\Entities\Property;
use Modules\RealEstate\Entities\RentalCard;
use Modules\RealEstate\Enums\RentalCardStatus;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\RealEstate\Entities\RentalCard>
 */
class RentalCardFactory extends Factory
{
    protected $model = RentalCard::class;

    public function definition(): array
    {
        $start = fake()->dateTimeBetween('-1 month', '+1 month');
        $end = (new \DateTime($start->format('Y-m-d')))
            ->modify('+'.fake()->numberBetween(1, 12).' months');

        return [
            'property_id' => Property::factory(),
            'owner_id' => User::factory(),
            'tenant_user_id' => User::factory(),
            'external_tenant_name' => null,
            'external_tenant_phone' => null,
            'external_tenant_email' => null,
            'external_tenant_id_notes' => null,
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),
            'terms' => fake()->optional(0.7)->paragraph(),
            'notes' => fake()->optional(0.5)->sentence(),
            'is_renewable' => fake()->boolean(30),
            'status' => RentalCardStatus::ACTIVE,
        ];
    }

    public function external(): static
    {
        return $this->state(fn () => [
            'tenant_user_id' => null,
            'external_tenant_name' => fake()->name(),
            'external_tenant_phone' => fake()->phoneNumber(),
            'external_tenant_email' => fake()->safeEmail(),
        ]);
    }

    public function ended(): static
    {
        return $this->state(fn () => [
            'status' => RentalCardStatus::ENDED,
            'ended_at' => now(),
        ]);
    }
}
