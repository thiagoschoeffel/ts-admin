<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryReservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'production_pointing_id',
        'raw_material_id',
        'reserved_kg',
        'consumed_kg',
        'status',
    ];

    protected $casts = [
        'reserved_kg' => 'decimal:3',
        'consumed_kg' => 'decimal:3',
    ];

    public function productionPointing(): BelongsTo
    {
        return $this->belongsTo(ProductionPointing::class);
    }

    public function rawMaterial(): BelongsTo
    {
        return $this->belongsTo(RawMaterial::class);
    }
}

