<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ClientPolicy
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
        return (bool)($permissions['clients']['view'] ?? false);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Client $client): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $permissions = $user->permissions ?? [];
        return (bool)($permissions['clients']['view'] ?? false);
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
        return (bool)($permissions['clients']['create'] ?? false);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Client $client): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $permissions = $user->permissions ?? [];
        return (bool)($permissions['clients']['update'] ?? false);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Client $client): bool|Response
    {
        if ($client->orders()->exists()) {
            return Response::deny(__('client.delete_blocked_has_orders'));
        }

        if ($user->isAdmin()) {
            return true;
        }

        $permissions = $user->permissions ?? [];
        return (bool)($permissions['clients']['delete'] ?? false);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Client $client): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $permissions = $user->permissions ?? [];
        return (bool)($permissions['clients']['update'] ?? false);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Client $client): bool|Response
    {
        if ($client->orders()->exists()) {
            return Response::deny(__('client.delete_blocked_has_orders'));
        }

        if ($user->isAdmin()) {
            return true;
        }

        $permissions = $user->permissions ?? [];
        return (bool)($permissions['clients']['delete'] ?? false);
    }

    /**
     * Determine whether the user can manage addresses for this client.
     */
    public function manageAddresses(User $user, Client $client): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $permissions = $user->permissions ?? [];
        return (bool)($permissions['clients']['view'] ?? false);
    }

    /**
     * Determine whether the user can create addresses for this client.
     */
    public function createAddress(User $user, Client $client): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $permissions = $user->permissions ?? [];
        return (bool)($permissions['clients']['create'] ?? false) || (bool)($permissions['clients']['update'] ?? false);
    }

    /**
     * Determine whether the user can update addresses for this client.
     */
    public function updateAddress(User $user, Client $client): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $permissions = $user->permissions ?? [];
        return (bool)($permissions['clients']['update'] ?? false);
    }

    /**
     * Determine whether the user can delete addresses for this client.
     */
    public function deleteAddress(User $user, Client $client): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $permissions = $user->permissions ?? [];
        return (bool)($permissions['clients']['update'] ?? false) || (bool)($permissions['clients']['delete'] ?? false);
    }
}
