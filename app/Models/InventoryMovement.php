<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Observers\InventoryMovementObserver;

class InventoryMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'occurred_at',
        'item_type',
        'item_id',
        'block_type_id',
        'mold_type_id',
        'length_mm',
        'width_mm',
        'height_mm',
        'location_type',
        'location_id',
        'direction',
        'quantity',
        'unit',
        'reference_type',
        'reference_id',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'quantity' => 'decimal:3',
    ];

    protected static function booted(): void
    {
        static::observe(InventoryMovementObserver::class);
    }

    public function rawMaterial()
    {
        return $this->belongsTo(RawMaterial::class, 'item_id');
    }

    public function silo()
    {
        return $this->belongsTo(Silo::class, 'location_id');
    }

    public function blockType()
    {
        return $this->belongsTo(BlockType::class);
    }

    public function almoxarifado()
    {
        return $this->belongsTo(Almoxarifado::class, 'location_id');
    }

    public function moldType()
    {
        return $this->belongsTo(MoldType::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
