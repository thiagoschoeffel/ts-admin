<?php

namespace App\Policies;

use App\Models\ReasonType;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ReasonTypePolicy
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
        return (bool)($permissions['reason_types']['view'] ?? false);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ReasonType $reasonType): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $permissions = $user->permissions ?? [];
        return (bool)($permissions['reason_types']['view'] ?? false);
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
        return (bool)($permissions['reason_types']['create'] ?? false);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ReasonType $reasonType): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $permissions = $user->permissions ?? [];
        return (bool)($permissions['reason_types']['update'] ?? false);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ReasonType $reasonType): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $permissions = $user->permissions ?? [];
        return (bool)($permissions['reason_types']['delete'] ?? false);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ReasonType $reasonType): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ReasonType $reasonType): bool
    {
        return false;
    }
}
