<?php

namespace App\Policies;

use App\Models\Silo;
use App\Models\User;

class SiloPolicy
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
    return (bool)($permissions['silos']['view'] ?? false);
  }

  /**
   * Determine whether the user can view the model.
   */
  public function view(User $user, Silo $silo): bool
  {
    if ($user->isAdmin()) {
      return true;
    }

    $permissions = $user->permissions ?? [];
    return (bool)($permissions['silos']['view'] ?? false);
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
    return (bool)($permissions['silos']['create'] ?? false);
  }

  /**
   * Determine whether the user can update the model.
   */
  public function update(User $user, Silo $silo): bool
  {
    if ($user->isAdmin()) {
      return true;
    }

    $permissions = $user->permissions ?? [];
    return (bool)($permissions['silos']['update'] ?? false);
  }

  /**
   * Determine whether the user can delete the model.
   */
  public function delete(User $user, Silo $silo): bool
  {
    if ($user->isAdmin()) {
      return true;
    }

    $permissions = $user->permissions ?? [];
    return (bool)($permissions['silos']['delete'] ?? false);
  }
}
