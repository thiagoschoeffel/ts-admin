<?php

namespace App\Observers;

use App\Models\Client;
use DomainException;

class ClientObserver
{
    public function deleting(Client $client): void
    {
        if ($client->orders()->exists()) {
            throw new DomainException(__('client.delete_blocked_has_orders'));
        }
    }
}
