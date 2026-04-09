<?php

namespace App\Models;

use App\Models\Operator;
use App\Models\RawMaterial;
use App\Models\Silo;
use App\Observers\ProductionPointingObserver;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ProductionPointing extends Model
{
    use HasFactory;

    protected $fillable = [
        'status',
        'sheet_number',
        'started_at',
        'ended_at',
        'raw_material_id',
        'quantity',
    ];

    protected $casts = [
        'status' => 'string',
        'sheet_number' => 'integer',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'quantity' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::observe(ProductionPointingObserver::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function rawMaterial(): BelongsTo
    {
        return $this->belongsTo(RawMaterial::class);
    }

    public function operators(): BelongsToMany
    {
        return $this->belongsToMany(Operator::class, 'production_pointing_operator')
            ->withTimestamps()
            ->orderBy('name');
    }

    public function silos(): BelongsToMany
    {
        return $this->belongsToMany(Silo::class, 'production_pointing_silo')
            ->withTimestamps()
            ->orderBy('name');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeSearch($query, $term)
    {
        return $query->where(function ($inner) use ($term): void {
            if (is_numeric($term)) {
                $inner->orWhere('sheet_number', (int) $term);
            }
            $inner->orWhere('sheet_number', 'like', '%' . $term . '%');
            $inner->orWhereHas('rawMaterial', function ($raw) use ($term): void {
                $raw->where('name', 'like', '%' . $term . '%');
            });
        });
    }

    public function scopeBetween($query, ?string $from, ?string $to)
    {
        if ($from) {
            $query->where('started_at', '>=', $from);
        }
        if ($to) {
            $query->where('ended_at', '<=', $to);
        }
        return $query;
    }
}
