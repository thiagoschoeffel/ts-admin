<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OpportunityItem extends Model
{
  use HasFactory;

  protected $fillable = [
    'opportunity_id',
    'product_id',
    'quantity',
    'unit_price',
    'subtotal',
  ];

  public function opportunity()
  {
    return $this->belongsTo(Opportunity::class);
  }

  public function product()
  {
    return $this->belongsTo(Product::class);
  }
}
