<?php

namespace App\Policies;

use App\Models\MoldType;
use App\Models\User;

class MoldTypePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $permissions = $user->permissions ?? [];
        return (bool)($permissions['mold_types']['view'] ?? false);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, MoldType $moldType): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $permissions = $user->permissions ?? [];
        return (bool)($permissions['mold_types']['view'] ?? false);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $permissions = $user->permissions ?? [];
        return (bool)($permissions['mold_types']['create'] ?? false);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, MoldType $moldType): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $permissions = $user->permissions ?? [];
        return (bool)($permissions['mold_types']['update'] ?? false);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, MoldType $moldType): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $permissions = $user->permissions ?? [];
        return (bool)($permissions['mold_types']['delete'] ?? false);
    }
}