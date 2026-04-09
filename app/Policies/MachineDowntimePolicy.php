<?php

namespace App\Policies;

use App\Models\MachineDowntime;
use App\Models\User;

class MachineDowntimePolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }
        $permissions = $user->permissions ?? [];
        return (bool)($permissions['machine_downtimes']['view'] ?? false);
    }

    public function view(User $user, MachineDowntime $model): bool
    {
        if ($user->isAdmin()) {
            return true;
        }
        $permissions = $user->permissions ?? [];
        return (bool)($permissions['machine_downtimes']['view'] ?? false);
    }

    public function create(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }
        $permissions = $user->permissions ?? [];
        return (bool)($permissions['machine_downtimes']['create'] ?? false);
    }

    public function update(User $user, MachineDowntime $model): bool
    {
        if ($user->isAdmin()) {
            return true;
        }
        $permissions = $user->permissions ?? [];
        return (bool)($permissions['machine_downtimes']['update'] ?? false);
    }

    public function delete(User $user, MachineDowntime $model): bool
    {
        if ($user->isAdmin()) {
            return true;
        }
        $permissions = $user->permissions ?? [];
        return (bool)($permissions['machine_downtimes']['delete'] ?? false);
    }
}
