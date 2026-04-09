<?php

namespace App\Models;

use App\Observers\MoldedProductionObserver;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class MoldedProduction extends Model
{
    use HasFactory;

    protected $fillable = [
        'production_pointing_id',
        'mold_type_id',
        'started_at',
        'ended_at',
        'sheet_number',
        'quantity',
        'scrap_quantity',
        'scrap_reason_id',
        'package_weight',
        'package_quantity',
        'loss_factor_enabled',
        'loss_factor',
        'weight_considered_unit',
        'total_weight_considered',
        'created_by_id',
        'updated_by_id',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'quantity' => 'integer',
        'scrap_quantity' => 'integer',
        'scrap_reason_id' => 'integer',
        'package_quantity' => 'integer',
        'package_weight' => 'decimal:2',
        'weight_considered_unit' => 'decimal:3',
        'total_weight_considered' => 'decimal:2',
        'loss_factor_enabled' => 'boolean',
        'loss_factor' => 'decimal:4',
    ];

    protected static function booted(): void
    {
        static::observe(MoldedProductionObserver::class);
    }
    public function scrapReason(): BelongsTo
    {
        return $this->belongsTo(Reason::class, 'scrap_reason_id');
    }

    public function productionPointing(): BelongsTo
    {
        return $this->belongsTo(ProductionPointing::class);
    }

    public function moldType(): BelongsTo
    {
        return $this->belongsTo(MoldType::class);
    }

    public function operators(): BelongsToMany
    {
        return $this->belongsToMany(Operator::class, 'molded_production_operator');
    }

    public function silos(): BelongsToMany
    {
        return $this->belongsToMany(Silo::class, 'molded_production_silo');
    }

    public function scraps()
    {
        return $this->hasMany(MoldedProductionScrap::class);
    }
}
