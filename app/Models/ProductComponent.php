<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductComponent extends Model
{
    use HasFactory;

    protected $table = 'product_components';

    protected $fillable = [
        'product_id',
        'component_id',
        'quantity',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function component()
    {
        return $this->belongsTo(Product::class, 'component_id');
    }
}
