<?php

namespace App\Policies;

use App\Models\BlockDispatch;
use App\Models\User;

class BlockDispatchPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $permissions = $user->permissions ?? [];
        return (bool) ($permissions['block_dispatches']['view'] ?? false);
    }

    public function view(User $user, BlockDispatch $blockDispatch): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $permissions = $user->permissions ?? [];
        return (bool) ($permissions['block_dispatches']['view'] ?? false);
    }

    public function create(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $permissions = $user->permissions ?? [];
        return (bool) ($permissions['block_dispatches']['create'] ?? false);
    }

    public function update(User $user, BlockDispatch $blockDispatch): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $permissions = $user->permissions ?? [];
        return (bool) ($permissions['block_dispatches']['update'] ?? false);
    }

    public function delete(User $user, BlockDispatch $blockDispatch): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $permissions = $user->permissions ?? [];
        return (bool) ($permissions['block_dispatches']['delete'] ?? false);
    }
}

