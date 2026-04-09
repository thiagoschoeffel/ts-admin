<?php

namespace App\Policies;

use App\Models\MoldedDispatch;
use App\Models\User;

class MoldedDispatchPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $permissions = $user->permissions ?? [];
        return (bool) ($permissions['molded_dispatches']['view'] ?? false);
    }

    public function view(User $user, MoldedDispatch $moldedDispatch): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $permissions = $user->permissions ?? [];
        return (bool) ($permissions['molded_dispatches']['view'] ?? false);
    }

    public function create(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $permissions = $user->permissions ?? [];
        return (bool) ($permissions['molded_dispatches']['create'] ?? false);
    }

    public function update(User $user, MoldedDispatch $moldedDispatch): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $permissions = $user->permissions ?? [];
        return (bool) ($permissions['molded_dispatches']['update'] ?? false);
    }

    public function delete(User $user, MoldedDispatch $moldedDispatch): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $permissions = $user->permissions ?? [];
        return (bool) ($permissions['molded_dispatches']['delete'] ?? false);
    }
}

