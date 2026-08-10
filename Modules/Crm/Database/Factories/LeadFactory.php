<?php

namespace Modules\Crm\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Auth\Entities\User;
use Modules\Crm\Entities\Lead;
use Modules\Crm\Enums\LeadSource;
use Modules\Crm\Enums\LeadStatus;

class LeadFactory extends Factory
{
    protected $model = Lead::class;

    public function definition(): array
    {
        return [
            'trader_id' => User::factory(),
            'name' => $this->faker->name(),
            'phone' => $this->faker->numerify('05########'),
            'email' => $this->faker->safeEmail(),
            'source' => LeadSource::WEBSITE->value,
            'status' => LeadStatus::NEW->value,
            'status_changed_at' => now(),
            'last_activity_at' => now(),
        ];
    }
}
