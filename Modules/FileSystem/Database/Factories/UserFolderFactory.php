<?php

namespace Modules\FileSystem\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Auth\Entities\User;
use Modules\FileSystem\Entities\UserFolder;

class UserFolderFactory extends Factory
{
    protected $model = UserFolder::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'parent_id' => null,
            'name' => fake()->word(),
            'folder_type' => 'regular',
            'is_protected' => false,
        ];
    }

    public function property(): static
    {
        return $this->state(fn () => [
            'folder_type' => 'property',
            'is_protected' => true,
            'name' => 'Property Folder',
        ]);
    }

    public function general(): static
    {
        return $this->state(fn () => [
            'folder_type' => 'general',
            'is_protected' => true,
            'name' => 'General',
        ]);
    }

    public function withParent(UserFolder $parent): static
    {
        return $this->state(fn () => [
            'user_id' => $parent->user_id,
            'parent_id' => $parent->id,
        ]);
    }
}
