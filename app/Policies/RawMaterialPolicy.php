<?php

namespace App\Policies;

use App\Models\RawMaterial;
use App\Models\User;

class RawMaterialPolicy
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
        return (bool)($permissions['raw_materials']['view'] ?? false);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, RawMaterial $rawMaterial): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $permissions = $user->permissions ?? [];
        return (bool)($permissions['raw_materials']['view'] ?? false);
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
        return (bool)($permissions['raw_materials']['create'] ?? false);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, RawMaterial $rawMaterial): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $permissions = $user->permissions ?? [];
        return (bool)($permissions['raw_materials']['update'] ?? false);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, RawMaterial $rawMaterial): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $permissions = $user->permissions ?? [];
        return (bool)($permissions['raw_materials']['delete'] ?? false);
    }
}