<?php

namespace Modules\FileSystem\Policies;

use Modules\Auth\Entities\User;
use Modules\FileSystem\Entities\UserFile;

class FilePolicy
{
    public function before(?User $user, string $ability): ?bool
    {
        if ($user?->hasRole('super-admin')) {
            return true;
        }

        return $user?->can("files.{$ability}");
    }

    public function viewAny(User $user): bool
    {
        return $user->can('files.list');
    }

    public function view(User $user, UserFile $file): bool
    {
        return $file->user_id === $user->id || $user->can('files.show');
    }

    public function create(User $user): bool
    {
        return $user->can('files.create');
    }

    public function update(User $user, UserFile $file): bool
    {
        return $file->user_id === $user->id;
    }

    public function delete(User $user, UserFile $file): bool
    {
        return $file->user_id === $user->id;
    }

    public function move(User $user, UserFile $file): bool
    {
        return $file->user_id === $user->id;
    }

    public function rename(User $user, UserFile $file): bool
    {
        return $file->user_id === $user->id;
    }
}
