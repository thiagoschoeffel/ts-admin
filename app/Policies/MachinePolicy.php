<?php

namespace App\Policies;

use App\Models\Machine;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class MachinePolicy
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
        return (bool)($permissions['machines']['view'] ?? false);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Machine $machine): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $permissions = $user->permissions ?? [];
        return (bool)($permissions['machines']['view'] ?? false);
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
        return (bool)($permissions['machines']['create'] ?? false);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Machine $machine): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $permissions = $user->permissions ?? [];
        return (bool)($permissions['machines']['update'] ?? false);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Machine $machine): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $permissions = $user->permissions ?? [];
        return (bool)($permissions['machines']['delete'] ?? false);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Machine $machine): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Machine $machine): bool
    {
        return false;
    }
}
