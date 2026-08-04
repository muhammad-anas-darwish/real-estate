<?php

namespace Modules\Communication\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Auth\Entities\User;
use Modules\Communication\Entities\UserFcmToken;
use Modules\Communication\Enums\DeviceTypeEnum;

class UserFcmTokenFactory extends Factory
{
    protected $model = UserFcmToken::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'token' => fake()->uuid(),
            'device_type' => fake()->randomElement(DeviceTypeEnum::cases()),
            'last_used_at' => now(),
        ];
    }
}
