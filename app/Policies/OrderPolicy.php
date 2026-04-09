<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class OrderPolicy
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
        return (bool)($permissions['orders']['view'] ?? false);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Order $order): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $permissions = $user->permissions ?? [];
        return (bool)($permissions['orders']['view'] ?? false);
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
        return (bool)($permissions['orders']['create'] ?? false);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Order $order): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $permissions = $user->permissions ?? [];
        return (bool)($permissions['orders']['update'] ?? false);
    }

    /**
     * Determine whether the user can update the status of the order.
     */
    public function updateStatus(User $user, Order $order): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $permissions = $user->permissions ?? [];
        return (bool)($permissions['orders']['update_status'] ?? false);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Order $order)
    {
        if ($user->isAdmin()) {
            return $order->status === 'pending'
                ? Response::allow()
                : Response::deny(__('order.delete_blocked_not_pending'));
        }

        $permissions = $user->permissions ?? [];
        if (!(bool)($permissions['orders']['delete'] ?? false)) {
            return Response::deny();
        }

        return $order->status === 'pending'
            ? Response::allow()
            : Response::deny(__('order.delete_blocked_not_pending'));
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Order $order): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $permissions = $user->permissions ?? [];
        return (bool)($permissions['orders']['update'] ?? false);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Order $order)
    {
        if ($user->isAdmin()) {
            return $order->status === 'pending'
                ? Response::allow()
                : Response::deny(__('order.delete_blocked_not_pending'));
        }

        $permissions = $user->permissions ?? [];
        if (!(bool)($permissions['orders']['delete'] ?? false)) {
            return Response::deny();
        }

        return $order->status === 'pending'
            ? Response::allow()
            : Response::deny(__('order.delete_blocked_not_pending'));
    }

    /**
     * Determine whether the user can manage items for this order.
     */
    public function manageItems(User $user, Order $order): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $permissions = $user->permissions ?? [];
        return (bool)($permissions['orders']['view'] ?? false);
    }

    /**
     * Determine whether the user can add items to this order.
     */
    public function addItem(User $user, Order $order): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $permissions = $user->permissions ?? [];
        return (bool)($permissions['orders']['create'] ?? false) || (bool)($permissions['orders']['update'] ?? false);
    }

    /**
     * Determine whether the user can update items in this order.
     */
    public function updateItem(User $user, Order $order): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $permissions = $user->permissions ?? [];
        return (bool)($permissions['orders']['update'] ?? false);
    }

    /**
     * Determine whether the user can remove items from this order.
     */
    public function removeItem(User $user, Order $order): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $permissions = $user->permissions ?? [];
        return (bool)($permissions['orders']['update'] ?? false) || (bool)($permissions['orders']['delete'] ?? false);
    }

    /**
     * Determine whether the user can export PDF for the order.
     */
    public function exportPdf(User $user, Order $order): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $permissions = $user->permissions ?? [];
        return (bool)($permissions['orders']['export_pdf'] ?? false);
    }
}
