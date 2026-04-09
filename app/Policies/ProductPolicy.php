<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ProductPolicy
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
        return (bool)($permissions['products']['view'] ?? false);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Product $product): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $permissions = $user->permissions ?? [];
        return (bool)($permissions['products']['view'] ?? false);
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
        return (bool)($permissions['products']['create'] ?? false);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Product $product): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $permissions = $user->permissions ?? [];
        return (bool)($permissions['products']['update'] ?? false);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Product $product): bool|Response
    {
        if ($user->isAdmin()) {
            if ($product->orderItems()->exists()) {
                return Response::deny(__('product.delete_blocked_has_orders'));
            }
            return true;
        }

        $permissions = $user->permissions ?? [];
        $hasPermission = (bool)($permissions['products']['delete'] ?? false);

        if (!$hasPermission) {
            return false;
        }

        if ($product->orderItems()->exists()) {
            return Response::deny(__('product.delete_blocked_has_orders'));
        }

        return true;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Product $product): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $permissions = $user->permissions ?? [];
        return (bool)($permissions['products']['update'] ?? false);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Product $product): bool|Response
    {
        if ($user->isAdmin()) {
            if ($product->orderItems()->exists()) {
                return Response::deny(__('product.delete_blocked_has_orders'));
            }
            return true;
        }

        $permissions = $user->permissions ?? [];
        $hasPermission = (bool)($permissions['products']['delete'] ?? false);

        if (!$hasPermission) {
            return false;
        }

        // Check if product has associated orders
        return !$product->orderItems()->exists();
    }

    /**
     * Determine whether the user can manage components for this product.
     */
    public function manageComponents(User $user, Product $product): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $permissions = $user->permissions ?? [];
        return (bool)($permissions['products']['view'] ?? false);
    }

    /**
     * Determine whether the user can create components for this product.
     */
    public function createComponent(User $user, Product $product): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $permissions = $user->permissions ?? [];
        return (bool)($permissions['products']['create'] ?? false) || (bool)($permissions['products']['update'] ?? false);
    }

    /**
     * Determine whether the user can update components for this product.
     */
    public function updateComponent(User $user, Product $product): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $permissions = $user->permissions ?? [];
        return (bool)($permissions['products']['update'] ?? false);
    }

    /**
     * Determine whether the user can delete components for this product.
     */
    public function deleteComponent(User $user, Product $product): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $permissions = $user->permissions ?? [];
        return (bool)($permissions['products']['update'] ?? false) || (bool)($permissions['products']['delete'] ?? false);
    }
}
