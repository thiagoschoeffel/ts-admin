<?php

namespace App\Policies;

use App\Models\Almoxarifado;
use App\Models\User;

class AlmoxarifadoPolicy
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
        return (bool)($permissions['almoxarifados']['view'] ?? false);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Almoxarifado $almoxarifado): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $permissions = $user->permissions ?? [];
        return (bool)($permissions['almoxarifados']['view'] ?? false);
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
        return (bool)($permissions['almoxarifados']['create'] ?? false);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Almoxarifado $almoxarifado): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $permissions = $user->permissions ?? [];
        return (bool)($permissions['almoxarifados']['update'] ?? false);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Almoxarifado $almoxarifado): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $permissions = $user->permissions ?? [];
        return (bool)($permissions['almoxarifados']['delete'] ?? false);
    }
}