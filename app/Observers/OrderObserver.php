<?php

namespace App\Observers;

use App\Models\Order;
use DomainException;

class OrderObserver
{
    public function deleting(Order $order): void
    {
        if ($order->status !== 'pending') {
            throw new DomainException(__('order.delete_blocked_not_pending'));
        }
    }
}
