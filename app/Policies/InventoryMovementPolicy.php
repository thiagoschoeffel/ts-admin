<?php

namespace App\Policies;

use App\Models\InventoryMovement;
use App\Models\User;

class InventoryMovementPolicy
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
    return (bool)($permissions['inventory_movements']['view'] ?? false);
  }

  /**
   * Determine whether the user can view the model.
   */
  public function view(User $user, InventoryMovement $inventoryMovement): bool
  {
    if ($user->isAdmin()) {
      return true;
    }

    $permissions = $user->permissions ?? [];
    return (bool)($permissions['inventory_movements']['view'] ?? false);
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
    return (bool)($permissions['inventory_movements']['create'] ?? false);
  }

  /**
   * Determine whether the user can update the model.
   */
  public function update(User $user, InventoryMovement $inventoryMovement): bool
  {
    if ($user->isAdmin()) {
      return true;
    }

    $permissions = $user->permissions ?? [];
    return (bool)($permissions['inventory_movements']['update'] ?? false);
  }

  /**
   * Determine whether the user can delete the model.
   */
  public function delete(User $user, InventoryMovement $inventoryMovement): bool
  {
    if ($user->isAdmin()) {
      return true;
    }

    $permissions = $user->permissions ?? [];
    return (bool)($permissions['inventory_movements']['delete'] ?? false);
  }
}
