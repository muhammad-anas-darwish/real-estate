<?php

namespace Modules\Crm\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Auth\Entities\User;
use Modules\Crm\Entities\Lead;
use Modules\Crm\Entities\LeadNote;

class LeadNoteFactory extends Factory
{
    protected $model = LeadNote::class;

    public function definition(): array
    {
        return [
            'lead_id' => Lead::factory(),
            'author_id' => User::factory(),
            'body' => $this->faker->sentence(),
            'is_locked' => false,
        ];
    }
}
