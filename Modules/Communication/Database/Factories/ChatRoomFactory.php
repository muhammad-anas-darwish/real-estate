<?php

namespace Modules\Communication\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Auth\Entities\User;
use Modules\Communication\Entities\ChatRoom;
use Modules\Communication\Enums\RoomTypeEnum;

class ChatRoomFactory extends Factory
{
    protected $model = ChatRoom::class;

    public function definition(): array
    {
        return [
            'type' => fake()->randomElement(RoomTypeEnum::cases()),
            'name' => null,
            'property_id' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (ChatRoom $room) {
            if (!$room->participants()->exists()) {
                $room->participants()->attach([
                    User::factory()->id => ['joined_at' => now()],
                    User::factory()->id => ['joined_at' => now()],
                ]);
            }
        });
    }

    public function private(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => RoomTypeEnum::PRIVATE,
            'property_id' => null,
        ]);
    }

    public function group(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => RoomTypeEnum::GROUP,
            'name' => fake()->sentence(3),
        ]);
    }

    public function withParticipants(array $userIds): static
    {
        return $this->afterCreating(function (ChatRoom $room) use ($userIds) {
            foreach ($userIds as $userId) {
                $room->participants()->attach($userId, ['joined_at' => now()]);
            }
        });
    }
}