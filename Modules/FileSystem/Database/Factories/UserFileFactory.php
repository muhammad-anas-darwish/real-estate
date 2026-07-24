<?php

namespace Modules\FileSystem\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Auth\Entities\User;
use Modules\FileSystem\Entities\UserFile;
use Modules\FileSystem\Entities\UserFolder;
use Modules\FileSystem\Enums\FileType;

class UserFileFactory extends Factory
{
    protected $model = UserFile::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'folder_id' => UserFolder::factory(),
            'name' => fake()->word().'.txt',
            'file_type' => FileType::TEXT,
            'mime_type' => 'text/plain',
            'size' => fake()->numberBetween(1, 10000),
            'content' => fake()->paragraph(),
            'file_path' => null,
        ];
    }

    public function image(): static
    {
        return $this->state(fn () => [
            'file_type' => FileType::IMAGE,
            'mime_type' => 'image/jpeg',
            'name' => fake()->word().'.jpg',
            'content' => null,
            'file_path' => 'user-files/'.fake()->uuid().'.jpg',
            'size' => fake()->numberBetween(10_000, 500_000),
        ]);
    }

    public function emptyFile(): static
    {
        return $this->state(fn () => [
            'content' => '',
            'size' => 0,
        ]);
    }
}
