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
            'type' => RoomTypeEnum::PRIVATE,
            'name' => null,
            'property_id' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (ChatRoom $room) {
            if (! $room->participants()->count()) {
                $user1 = User::factory()->create();
                $user2 = User::factory()->create();

                \Illuminate\Support\Facades\DB::table('chat_room_participants')->insert([
                    'room_id' => $room->id,
                    'user_id' => $user1->id,
                    'joined_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                \Illuminate\Support\Facades\DB::table('chat_room_participants')->insert([
                    'room_id' => $room->id,
                    'user_id' => $user2->id,
                    'joined_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }

    public function withParticipants(array $userIds): static
    {
        return $this->afterCreating(function (ChatRoom $room) use ($userIds) {
            foreach ($userIds as $userId) {
                \Illuminate\Support\Facades\DB::table('chat_room_participants')->insert([
                    'room_id' => $room->id,
                    'user_id' => $userId,
                    'joined_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }
}
