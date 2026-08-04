<?php

namespace Modules\Communication\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Auth\Entities\User;
use Modules\Communication\Entities\ChatRoom;
use Modules\Communication\Entities\Message;
use Modules\Communication\Enums\MessageTypeEnum;

class MessageFactory extends Factory
{
    protected $model = Message::class;

    public function definition(): array
    {
        return [
            'room_id' => ChatRoom::factory(),
            'sender_id' => User::factory(),
            'body' => fake()->paragraphs(1, true),
            'type' => MessageTypeEnum::TEXT,
        ];
    }
}
