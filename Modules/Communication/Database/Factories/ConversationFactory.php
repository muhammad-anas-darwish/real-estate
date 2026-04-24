<?php

namespace Modules\Communication\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Auth\Entities\User;
use Modules\Communication\Entities\Conversation;
use Modules\Communication\Enums\ConversationType;
use Modules\RealEstate\Entities\Property;

class ConversationFactory extends Factory
{
    protected $model = Conversation::class;

    public function definition(): array
    {
        return [
            'property_id' => Property::inRandomOrder()->first()?->id ?? Property::factory(),
            'type' => fake()->randomElement(ConversationType::cases()),
            'initiator_id' => User::factory(),
            'recipient_id' => User::factory(),
        ];
    }
}