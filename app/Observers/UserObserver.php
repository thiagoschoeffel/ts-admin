<?php

namespace App\Observers;

use App\Models\User;
use DomainException;

class UserObserver
{
    public function deleting(User $user): void
    {
        if ($user->clients()->exists() || $user->products()->exists() || $user->orders()->exists()) {
            throw new DomainException(__('user.delete_blocked_has_related_records'));
        }
    }
}
