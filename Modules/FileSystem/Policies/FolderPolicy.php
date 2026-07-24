<?php

namespace Modules\FileSystem\Policies;

use Modules\Auth\Entities\User;
use Modules\FileSystem\Entities\UserFolder;

class FolderPolicy
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

    public function view(User $user, UserFolder $folder): bool
    {
        return $folder->user_id === $user->id || $user->can('files.show');
    }

    public function create(User $user): bool
    {
        return $user->can('files.create');
    }

    public function update(User $user, UserFolder $folder): bool
    {
        return $folder->user_id === $user->id;
    }

    public function delete(User $user, UserFolder $folder): bool
    {
        if ($folder->is_protected) {
            return false;
        }

        return $folder->user_id === $user->id;
    }

    public function move(User $user, UserFolder $folder): bool
    {
        return $folder->isMovable() && $folder->user_id === $user->id;
    }

    public function rename(User $user, UserFolder $folder): bool
    {
        return $folder->isMovable() && $folder->user_id === $user->id;
    }
}
