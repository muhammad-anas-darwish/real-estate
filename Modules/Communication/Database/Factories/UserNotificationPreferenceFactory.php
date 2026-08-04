<?php

namespace Modules\Communication\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Auth\Entities\User;
use Modules\Communication\Entities\UserNotificationPreference;

class UserNotificationPreferenceFactory extends Factory
{
    protected $model = UserNotificationPreference::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'channel' => fake()->randomElement(['fcm', 'pusher', 'email']),
            'enabled' => true,
        ];
    }
}
