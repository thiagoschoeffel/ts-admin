<?php

namespace App\Models;

use App\Observers\BlockProductionObserver;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class BlockProduction extends Model
{
    use HasFactory;

    protected $fillable = [
        'production_pointing_id',
        'block_type_id',
        'started_at',
        'ended_at',
        'sheet_number',
        'weight',
        'length_mm',
        'width_mm',
        'height_mm',
        'is_scrap',
        'dimension_customization_enabled',
        'created_by_id',
        'updated_by_id',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'weight' => 'decimal:2',
        'length_mm' => 'integer',
        'width_mm' => 'integer',
        'height_mm' => 'integer',
        'is_scrap' => 'boolean',
        'dimension_customization_enabled' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::observe(BlockProductionObserver::class);
    }

    public function productionPointing(): BelongsTo
    {
        return $this->belongsTo(ProductionPointing::class);
    }

    public function blockType(): BelongsTo
    {
        return $this->belongsTo(BlockType::class);
    }

    public function operators(): BelongsToMany
    {
        return $this->belongsToMany(Operator::class, 'block_production_operator');
    }

    public function silos(): BelongsToMany
    {
        return $this->belongsToMany(Silo::class, 'block_production_silo');
    }
}
