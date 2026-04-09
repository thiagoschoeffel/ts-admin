<?php

namespace App\Models;

use App\Observers\MoldTypeObserver;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MoldType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'pieces_per_package',
        'status',
    ];

    protected $casts = [
        'status' => 'string',
        'pieces_per_package' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::observe(MoldTypeObserver::class);
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