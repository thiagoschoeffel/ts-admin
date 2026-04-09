<?php

namespace App\Observers;

use App\Models\Product;
use DomainException;

class ProductObserver
{
    public function deleting(Product $product): void
    {
        if ($product->orderItems()->exists()) {
            throw new DomainException(__('product.delete_blocked_has_orders'));
        }
    }
}
