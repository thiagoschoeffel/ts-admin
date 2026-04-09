<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BlockDispatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'dispatched_at',
        'manufacturing_order_number',
        'production_pointing_id',
        'created_by_id',
        'updated_by_id',
    ];

    protected $casts = [
        'dispatched_at' => 'datetime',
    ];

    public function productionPointing(): BelongsTo
    {
        return $this->belongsTo(ProductionPointing::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(BlockDispatchItem::class);
    }
}
