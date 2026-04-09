<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, User $model): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $model): bool
    {
        // Admin pode editar qualquer usuário, exceto ele mesmo
        return $user->isAdmin() && $user->id !== $model->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): bool|Response
    {
        // Admin pode excluir qualquer usuário, exceto ele mesmo
        if (!$user->isAdmin() || $user->id === $model->id) {
            return false;
        }

        if ($model->clients()->exists() || $model->products()->exists() || $model->orders()->exists()) {
            return Response::deny(__('user.delete_blocked_has_related_records'));
        }

        return true;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, User $model): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, User $model): bool|Response
    {
        if (!$user->isAdmin()) {
            return false;
        }

        if ($model->clients()->exists() || $model->products()->exists() || $model->orders()->exists()) {
            return Response::deny(__('user.delete_blocked_has_related_records'));
        }

        return true;
    }
}
