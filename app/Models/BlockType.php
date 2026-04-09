<?php

namespace App\Models;

use App\Observers\BlockTypeObserver;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlockType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'raw_material_percentage',
        'status',
    ];

    protected $casts = [
        'status' => 'string',
        'raw_material_percentage' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::observe(BlockTypeObserver::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeSearch($query, $term)
    {
        return $query->where('name', 'like', "%{$term}%");
    }
}