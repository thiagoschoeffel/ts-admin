<?php

namespace App\Models;

use App\Observers\ProductObserver;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'price',
        'unit_of_measure',
        'status',
        'length',
        'width',
        'height',
        'weight',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected static function booted(): void
    {
        static::observe(ProductObserver::class);
    }

    // Produtos que compõem este produto
    public function components()
    {
        return $this->belongsToMany(
            Product::class,
            'product_components',
            'product_id',
            'component_id'
        )->withPivot('id', 'quantity')->withTimestamps();
    }

    // Produtos dos quais este produto faz parte
    public function parents()
    {
        return $this->belongsToMany(
            Product::class,
            'product_components',
            'component_id',
            'product_id'
        )->withPivot('id', 'quantity')->withTimestamps();
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function formattedPrice(): string
    {
        return 'R$ ' . number_format($this->price, 2, ',', '.');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
